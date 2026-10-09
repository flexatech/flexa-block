# MCP Module

> ✅ xong (12) · 🧪 đang làm · ⬜ chưa bắt đầu (15)
> · Cập nhật: 2026-10-09
>
> Module MCP nằm **trong** plugin free này, dưới `includes/mcp/`, tắt mặc định.
> Quyết định kiến trúc và lý do: [`mcp-module-plan-prompt.md`](mcp-module-plan-prompt.md).
> File này là thứ tự thi công; prompt kia là đầu vào để sinh plan chi tiết.
>
> **Phụ thuộc bên ngoài**: WordPress 7.0+ (Abilities API trong core) và plugin
> `mcp-adapter` 0.7.x trên WP.org (nó sở hữu MCP server, transport, auth: không
> có gì trong ba thứ đó là việc của chúng ta). `FLEXA_BLOCK_MIN_WP` giữ ở `6.4`.

---

## Vì sao thứ tự này

Hai nguyên tắc chi phối cách chia giai đoạn:

1. **Giai đoạn 0 phát hành được một mình.** Hết giai đoạn 0 là một bản update an
   toàn: có công tắc, không expose ability nào. Nếu các giai đoạn sau trượt,
   phần đã ship không nợ gì ai.
2. **Slot layer (giai đoạn 2) test qua đường admin import trước, không qua MCP.**
   Đây là phần mới hoàn toàn và dễ sai nhất. Nếu gộp chung với write ability thì
   khi lỗi sẽ không biết hỏng ở slot hay ở MCP. Tách ra, slot được kiểm bằng nút
   import sẵn có trong `samples-panel.tsx`, rồi mới cắm MCP vào thứ đã chạy.

## Giai đoạn 0 · Nền và công tắc

Không ability nào. Mục tiêu: bật/tắt được, và tắt thì tuyệt đối trơ.

1. ✅ `includes/admin/class-mcp-settings.php`: option `flexa_block_mcp`, DEFAULTS,
   get/save, sanitize, REST `flexa-block/v1/mcp` (GET/POST, `manage_options`).
   Permission callback viết tại chỗ, không gọi `Admin::rest_permission()`, để sau
   này tách file ra plugin riêng không phải sửa.
2. ✅ Guard WP 7.0 ở save path. Chặn cả form post, `update_option()` từ WP-CLI, và
   payload settings import. Tầng đã chọn: `pre_update_option_flexa_block_mcp`,
   vì sanitizer chỉ thấy payload đi qua `save_settings()`, còn filter phủ cả REST
   route, `wp option update`, script migration và payload settings restore. Lý do
   nằm trong docblock của `MCP_Settings::guard_write()`.
3. ✅ Key `mcp` trong boot payload `flexaBlockAdmin`
   (`includes/admin/class-admin.php` ~447):
   `{ supported, minWp, enabled, read, write, adapter: { active, version }, endpoint, restUrl }`.
   Panel đọc từ đây nên vẽ được cả khi module đang tắt.
4. ✅ `includes/mcp/class-mcp-manager.php`: `init()`, soft-detect `mcp-adapter`,
   hook `wp_abilities_api_init`, filter `flexa_block_mcp_abilities` (tên đề xuất)
   để sau này plugin Flexa khác góp ability. Chưa đăng ký ability nào.
5. ✅ `flexa-block.php`: require `class-mcp-settings.php` trong admin context
   (luôn luôn), và require `includes/mcp/` **chỉ khi** option bật và
   `version_compare( get_bloginfo('version'), '7.0', '>=' )`. Flag đã lưu không
   được tin một mình: site có thể bật ở 7.0 rồi restore backup về 6.9.
6. ✅ `src/admin/mcp-panel.tsx` + nav entry và view branch trong
   `src/admin/index.tsx`. Nav item **luôn hiện**, kể cả WP < 7.0. Bốn trạng thái:
   - WP < 7.0: toggle disabled, một câu lý do, không request nào.
   - WP 7.0+, tắt: công tắc và mô tả ngắn việc bật sẽ mở ra cái gì.
   - Bật, thiếu `mcp-adapter`: giải thích adapter, và nói rõ ability vẫn gọi được
     qua route abilities của core.
   - Bật, có adapter: endpoint, config copy được, setup guide, toggle read/write,
     phạm vi user và nội dung, activity.
7. ✅ Panel save tường minh, không tham gia auto-save debounced của app, và hành
   động bật có confirm.
8. ✅ `uninstall.php`: xoá `flexa_block_mcp` và transient của nó.

**Xong khi**: site 6.4 thấy nav item với toggle disabled; bật trên 7.1 thì module
load, `discover-abilities` của adapter không trả ability Flexa nào; `wp option
update flexa_block_mcp` để bật trên 6.9 bị từ chối; lưu settings chung không đụng
option mới và lưu option mới không flush CSS cache; request front-end của site
chưa bật không load thêm file nào.

**Đã lệch có chủ ý** (2026-10-09, lúc đóng giai đoạn 0):

- Soft-detect `mcp-adapter` nằm ở `MCP_Settings::adapter_state()` chứ ở
  `MCP_Manager` như mục 4 viết. Panel cần biết adapter có hay không ngay cả khi
  module đang tắt, mà lúc đó `includes/mcp/` không được load.
- Detect theo tên thư mục plugin (`mcp-adapter`) thay vì tên class. Giữ nguyên:
  slug WP.org là hợp đồng công bố, nội bộ adapter thì không.
- ~~`endpoint()` trả `''` thay vì đoán URL~~ **đã chốt 2026-10-09** trên WP 7.1.3
  với `mcp-adapter` 0.7.0 cài thật. `endpoint()` giờ có hai nhánh, vì ngoài
  WP-CLI adapter chỉ `init()` trên `rest_api_init` (p15): lúc render trang admin
  chưa có server nào để hỏi, nên trả route mặc định `mcp/mcp-adapter-default-server`
  mà adapter công bố cho 0.7.x; trong request REST, sau khi `mcp_adapter_init` đã
  chạy thì đọc thẳng `get_server_route_namespace()` + `get_server_route()` của
  server. Nhánh REST là cái duy nhất còn đúng khi site filter
  `mcp_adapter_default_server_config`, và trả `''` đúng như mong đợi khi site tắt
  default server qua `mcp_adapter_create_default_server`. Panel đọc lại payload
  từ response mỗi lần lưu nên site đã đổi route sẽ thấy giá trị thật ngay khi
  chạm một toggle. Filter `flexa_block_mcp_endpoint` vẫn phủ lên trên cùng.
  Đã kiểm cả bốn nhánh trên site thật; route sống, GET không auth trả 401.
  `phpstan.neon.dist` thêm một `ignoreErrors` hẹp cho `class.notFound` trong
  đúng file này, vì adapter là dependency tuỳ chọn không vendor.
- Trạng thái "bật, có adapter" ở mục 6 ship toggle read/write, đoạn mô tả phạm vi
  và card endpoint có điều kiện. Config copy được, setup guide và activity thuộc
  mục 22-23 nên để lại giai đoạn 4.

## Giai đoạn 1 · Ability chỉ đọc

Hai ability, không ghi gì. Mục tiêu: một agent trả lời được hai câu hỏi phải có
trước khi viết được gì hữu ích cho plugin này, site trông thế nào và trang này
đang có gì.

9. ✅ `includes/mcp/class-ability-support.php`: `read_ability()` điền category,
   annotation readonly và cờ expose; `requires( $cap )` dựng permission callback;
   `bound()` / `bound_deep()` chặn độ dài; `json_object()` giữ map rỗng encode
   thành `{}` chứ không phải `[]`.
10. ✅ `flexa/get-design-context`: token light và dark từ `Global_Styles`, ba cờ
    dark mode từ `Dark_Mode_Settings`, block khả dụng từ `Block_Manager`. Không
    `flexa_block_settings`, không chẩn đoán môi trường. Cần capability
    `edit_posts`.
11. ✅ `flexa/get-page-block-tree`: `parse_blocks()`, permission callback dùng
    `current_user_can( 'read_post', $id )`. `attributes` là đúng thứ post content
    lưu, `resolved_attributes` là bản đã điền default của block type và phải xin
    bằng `include_resolved`. Giới hạn 1000 node, sâu 20 cấp, mỗi value 2000 ký
    tự, cắt ở đâu thì báo `truncated`.
12. ✅ Toggle read riêng: `Read_Abilities::contribute()` trả rỗng khi
    `MCP_Settings::allows_read()` false, nên tắt read là hai ability không tồn
    tại chứ không phải tồn tại rồi bị từ chối.

**Transport đã kiểm thật, 2026-10-09.** Một client HTTP nối được vào
`/wp-json/mcp/mcp-adapter-default-server` bằng Application Password (Basic auth),
auth mặc định chỉ đòi capability `read`. Những thứ revision `2026-07-28` bắt buộc,
mỗi cái sai đều trả HTTP 400 nghe như lỗi transport chứ không phải lỗi tham số:

- Header mirror: `Mcp-Method` phải bằng method trong body; `tools/call`,
  `resources/read`, `prompts/get` phải có thêm `Mcp-Name` khớp `params.name`
  (`params.uri` cho resources/read); argument nào có annotation `x-mcp-header`
  thì cần `Mcp-Param-<name>`. Lỗi `-32020`.
- Không có session: **mọi** request phải tự mang `params._meta` với
  `io.modelcontextprotocol/protocolVersion` đúng revision và
  `io.modelcontextprotocol/clientCapabilities` là object. Lỗi `-32602`. Hệ quả:
  `initialize` không còn là bước bắt buộc, gọi nó trả thẳng "Method not found".

Revision `2025-11-25` thì vẫn theo lối cũ, `initialize` rồi `Mcp-Session-Id`.
Claude Code nối vào endpoint này báo Connected, tức là nó đi đường `2025-11-25`.
Script kiểm từng bước nằm ngoài repo, ở scratchpad (`mcp-bridge-test.sh`); mục 23
sẽ cần nó, lúc đó quyết định có đưa vào repo hay không.

⚠️ **Site này đã expose sẵn ability ghi mà module của ta không kiểm soát.**
`discover-abilities` với account role `editor` trả về `core/get-site-info`,
`core/get-user-info`, `core/get-environment-info` và **mười ability WooCommerce**,
trong đó có `product-create`, `product-update`, `product-delete`,
`order-update-status`, `order-add-note`. Chúng là của core và WooCommerce, lộ ra
ngay khi cắm `mcp-adapter`, không liên quan gì tới toggle của ta. Hai việc phải
làm vì chuyện này: chữ trong panel ("What agents may do") đang ngụ ý ta kiểm soát
phạm vi, mà thực tế ta chỉ kiểm soát phần ability của Flexa; và mục 24 phần
privacy/data-flow phải nói thẳng điều đó thay vì để người đọc tự suy ra.

**Mô hình expose, đọc từ `mcp-adapter` 0.7.0 ngày 2026-10-09**: ability là private
mặc định. Muốn lộ ra phải đặt `meta.public = true` hoặc `meta.mcp.public = true`
(`McpAbilityExposure::is_meta_public()`, trong đó `meta.mcp.public` thắng). Và
default server của adapter chỉ cầm đúng ba meta-tool `mcp-adapter/discover-abilities`,
`get-ability-info`, `execute-ability`: ability của ta tới tay client **qua
`execute-ability`**, không phải thành tool riêng ở cấp cao nhất, trừ khi ta nhét
tên chúng vào `tools` qua filter `mcp_adapter_default_server_config`, hoặc tự dựng
server riêng bằng `create_server()` trên hook `mcp_adapter_init`. Chọn đường nào là
quyết định của mục 9, không phải chi tiết thi công.

**Đường đi của ability, chốt ở mục 9**: giữ nguyên `execute-ability`, không nhồi
tên ability vào `tools` và không dựng server riêng. Lý do là mục 23: thêm một
server là thêm một endpoint phải viết hướng dẫn và phải bảo hành, còn cái ta được
lại chỉ là tên hiện ở cấp cao hơn trong danh sách tool của client. Đổi về sau vẫn
được bằng filter `mcp_adapter_default_server_config`, không phải quyết định một
lần.

**Xong rồi, kiểm thật ngày 2026-10-09.** `discover-abilities` qua Application
Password trả đúng hai ability của Flexa. `execute-ability` gọi
`get-design-context` ra 22 token light, 6 token dark override và 30 block đang
đăng ký. `get-page-block-tree` trên trang nhiều markup nhất của site cho 43 node
đúng cấu trúc lồng nhau, 16 KB, và 106 KB khi `include_resolved`: chênh gần bảy
lần, nên mặc định tắt là đúng. Các biên đã thử: `post_id` không tồn tại trả
`ability_invalid_permissions` chứ không trả 404, tức là không lộ việc post có hay
không; `post_id` 0 bị schema chặn; page private của admin đọc bằng account role
thấp trả `ability_invalid_permissions`; cùng account đó gọi `get-design-context`
cũng bị từ chối vì thiếu `edit_posts`; tắt read thì catalogue còn 0 ability.

Một chỗ phải sửa lại so với lời hứa cũ ở mục này: không có đường nào "trả về rỗng"
cho page không được đọc. Core biến mọi permission callback false thành
`ability_invalid_permissions` trước khi execute kịp chạy, nên câu trả lời là từ
chối. Từ chối tốt hơn: rỗng và không-có-quyền là hai chuyện khác nhau, gộp lại
thì client không phân biệt được.

Hai cái bẫy mà chỉ chạy thật mới thấy, ghi lại cho phase 3 đỡ mất buổi:

- Ability không khai `input_schema` thì core **từ chối mọi input**, kể cả
  `arguments: {}` mà client MCP nào cũng gửi. `get-design-context` không có tham
  số nào vẫn phải khai schema object rỗng, kèm `default` để lời gọi không input
  cũng chạy.
- `invoke_callback()` chỉ truyền `$input` cho callback khi ability có
  `input_schema`. Permission callback và execute callback phải khớp với chuyện
  đó.

## Giai đoạn 2 · Slot layer (phần khó nhất, chưa dính MCP)

13. ⬜ Chốt cơ chế slot. Hai sample hiện tại là markup cố định, placeholder duy
    nhất là `blockId`. Hướng đề xuất: preset khai báo `slots` map từ slot key đến
    block path + tên attribute, thay giá trị trên mảng `parse_blocks()` rồi
    `serialize_blocks()` lại. An toàn được chính vì cả 73 block đều dynamic và
    không attribute nào dùng `source`.
14. ⬜ `includes/import/class-preset-slots.php`: validate theo kiểu (text,
    multiline, URL, email, phone, media ID), giới hạn độ dài từng slot và trần
    payload, hành vi khi thiếu slot, thứ tự kết hợp với
    `regenerate_block_ids()` và resolve media.
15. ⬜ Khai báo `slots` cho preset đầu tiên trong `samples/contact-page.php`. Chỉ
    một preset. Chọn `landing-saas` sau khi preset đầu đã chạy.
16. ⬜ Versioning hợp đồng slot, để một bản Flexa Block sau không âm thầm làm hỏng
    draft đã tạo.
17. ⬜ Test: từng kiểu slot, vượt độ dài, thiếu slot, HTML và script nhồi vào slot
    text, media ID không có quyền.

**Xong khi**: điền slot qua đúng nút import trong `samples-panel.tsx` tạo ra page
mở trong Gutenberg không cảnh báo invalid block, save lại không đổi nội dung,
front-end render đúng và có CSS per-instance ngay lần xem đầu.

## Giai đoạn 3 · Ability ghi

18. ⬜ `includes/mcp/class-draft-writer.php`: wrapper quanh
    `Content_Importer::import()` ghim `post_status = draft`, và **không** để text
    do caller cung cấp đi qua đường `kses_remove_filters()` mà importer đang dùng
    (xem `class-content-importer.php` ~dòng 78). Chỉ nhận media ID đã tồn tại và
    caller có quyền. Validate toàn bộ request trước khi ghi.
19. ⬜ Idempotency key theo user + operation, lưu transient có TTL. `find_existing()`
    hiện key theo preset nên nó trả lời "preset này từng import chưa", không phải
    "request này xử lý chưa". Nói rõ nó tương tác thế nào với hành vi "mở bản cũ
    hay import bản mới" của admin.
20. ⬜ `flexa/list-presets`, `flexa/get-preset-schema`, `flexa/create-page-draft`.
21. ⬜ Giới hạn rate và kích thước request, có số cụ thể. Toggle write riêng, mặc
    định tắt kể cả khi MCP đã bật.

**Xong khi**: client thật chạy trọn luồng discover, đọc, tạo draft; mọi cố gắng
ép `publish` bị từ chối; gửi lại cùng idempotency key trả về đúng post cũ chứ
không tạo bản thứ hai.

## Giai đoạn 4 · Hoàn thiện để phát hành

22. ⬜ Activity log theo `flexa-plugin-activity-log`, sau khi đã kiểm xem
    observability của `mcp-adapter` ghi sẵn những gì để không trùng. Chỉ
    timestamp, user, ability, post ID, outcome, request ID. Không nội dung,
    không prompt, không token. Có retention và cleanup. Không mô tả nó như cơ chế
    rollback. Quyết định writer nằm trong module bị gate hay cạnh panel, vì khi
    module tắt thì không có gì để ghi.
23. ⬜ Setup guide cho đúng một client đã verify, và compatibility matrix chỉ gồm
    client/transport mà ta phát biểu được yêu cầu, kèm hai protocol revision mà
    `mcp-adapter` 0.7.x hỗ trợ. Không quảng cáo tương thích phổ quát.
24. ⬜ readme.txt: tính năng mới, tắt mặc định, cần WP 7.0 và `mcp-adapter`, giới
    hạn, mục privacy và data-flow, và nói thẳng rằng AI client do người dùng chọn
    sẽ nhận nội dung website qua các tool call được cho phép. Theo `wp-readme-txt`.
25. ⬜ Changelog, bump `FLEXA_BLOCK_VER`, xác nhận `Requires at least`,
    `Requires PHP`, `Tested up to` không cần đổi vì module tự gate bên trong.
26. ⬜ `makepot.sh`, Plugin Check, `phpcs.xml.dist`, `phpstan.neon.dist` sạch như
    baseline hiện tại.
27. ⬜ Regression: site không bật thì bản update không đổi gì, kể cả số file load
    trên một request front-end.

**Xong khi**: ZIP build từ `release.sh` cài lên site sạch WP 7.1, bật module, chạy
trọn luồng với client thật, và trên site WP 6.4 bản update không gây notice hay
fatal nào.

## Đang để mở

- Cơ chế slot cụ thể (mục 13) chốt sau khi có plan chi tiết từ
  `mcp-module-plan-prompt.md`. Hướng đề xuất ở trên là mặc định, không phải kết luận.
- Preset thứ hai (`landing-saas`) vào v1 hay v1.1.
- Admin import UI có dùng slot luôn hay giữ nguyên preset cố định (mục 15 chỉ cần
  slot cho đường MCP; mở rộng ra admin là quyết định riêng về scope).

## Rủi ro đã biết

- **Bề mặt bảo mật nằm trong plugin free**: một bản vá bảo mật ở module này là một
  release cả Flexa Block, không phải release nhỏ. Đây là giá đã chấp nhận khi chọn
  làm module thay vì plugin riêng.
- **Phụ thuộc một plugin bên thứ ba đang ở 0.x**: `mcp-adapter` 0.7.0 chưa 1.0, API
  còn có thể đổi. Vì thế không bao giờ đặt `Requires Plugins: mcp-adapter` vào
  header, và phải soft-detect kèm báo version trong panel.
- **`get-page-block-tree` không round-trip được attribute lồng nhau.** PHP decode
  JSON ra array, nên `{}` rỗng và `[]` rỗng ở cấp lồng bên trong một attribute là
  một thứ: output báo `[]` cho cả hai. Cấp ngoài cùng của mỗi attribute thì đã
  giữ đúng object. Chuyện này không sao với đường đọc, nhưng mục 18 tuyệt đối
  không được lấy output của ability này ghi thẳng ngược lại vào post. Write đi
  qua preset và slot, không qua echo attribute, nên hiện tại chỉ là giới hạn cần
  biết chứ không phải việc phải sửa.
- **`Content_Importer::import()` bỏ KSES** (`kses_remove_filters()`). An toàn cho
  nút admin với markup do plugin bundle, không an toàn cho đường MCP. Mục 18 tồn
  tại chỉ để chặn chuyện này; đừng gọi importer trực tiếp từ ability.
