# MCP Module

> ✅ xong (27) · 🧪 đang làm · ⬜ chưa bắt đầu (0)
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

13. ✅ Chốt cơ chế slot. Hai sample hiện tại là markup cố định, placeholder duy
    nhất là `blockId`. Hướng đề xuất: preset khai báo `slots` map từ slot key đến
    block path + tên attribute, thay giá trị trên mảng `parse_blocks()` rồi
    `serialize_blocks()` lại. An toàn được chính vì cả 73 block đều dynamic và
    không attribute nào dùng `source`.
14. ✅ `includes/import/class-preset-slots.php`: validate theo kiểu (text,
    multiline, URL, email, phone, media ID), giới hạn độ dài từng slot và trần
    payload, hành vi khi thiếu slot, thứ tự kết hợp với
    `regenerate_block_ids()` và resolve media.
15. ✅ Khai báo `slots` cho preset đầu tiên trong `samples/contact-page.php`. Chỉ
    một preset. Chọn `landing-saas` sau khi preset đầu đã chạy.
16. ✅ Versioning hợp đồng slot, để một bản Flexa Block sau không âm thầm làm hỏng
    draft đã tạo.
17. ✅ Test: từng kiểu slot, vượt độ dài, thiếu slot, HTML và script nhồi vào slot
    text, media ID không có quyền.

**Xong khi**: điền slot qua đúng nút import trong `samples-panel.tsx` tạo ra page
mở trong Gutenberg không cảnh báo invalid block, save lại không đổi nội dung,
front-end render đúng và có CSS per-instance ngay lần xem đầu.

**Cơ chế chốt khác đề xuất ở mục 13.** Không map slot theo block path. Một path
kiểu `0.1.2` là chỉ số trong cây, nên chỉ cần đổi thứ tự một block trong preset là
slot im lặng trỏ sang chỗ khác, và lỗi đó không có cách nào phát hiện lúc chạy.
Thay bằng token đặt thẳng trong giá trị attribute: `{{slot:key}}`, hoặc
`{{slot:key|variant}}` khi cùng một giá trị phải xuất hiện ở hai dạng (số điện
thoại hiện ra cho người đọc, và `tel:` trong link). Preset tự nói chỗ nào nhận
giá trị, đổi thứ tự block không ảnh hưởng gì.

Vẫn `parse_blocks()` rồi `serialize_blocks()` như mục 13 đề xuất, nhưng chỉ thay
trên **giá trị attribute**, không bao giờ thay trong markup đã lưu. Markup là HTML
đã escape: giá trị nhét vào đó sẽ bị đọc như markup. Attribute thì nằm trong JSON
bên trong comment delimiter, và `serialize_blocks()` tự escape lại, nên ký tự
hiểm như `-->` ra thành `\u002d\u002d\u003e`, không cắt được delimiter.

**Thứ tự ba lượt trong `Content_Importer::import()`**: `regenerate_block_ids()`,
rồi resolve media, rồi slot. Chữ của người dùng vào sau cùng, nên không lượt nào
còn cơ hội đọc lại nó.

**Hợp đồng slot (mục 16)**: preset khai `slot_contract`. Bản Flexa Block nào không
hiểu số đó thì rút slot đi, không chào ra UI và cũng không nhận vào, nhưng vẫn
điền default để trang nhập về đúng như preset xuất xưởng, kèm một dòng báo cho
người dùng. Draft đã tạo không bị đụng tới: giá trị nằm trong post, không nằm
trong hợp đồng. Post được đóng dấu `_flexa_slot_contract` và `_flexa_slot_keys`.

**Hai lỗi do kiểm thật trên site bắt được, không phải do đọc code.** Thứ nhất,
`max` của một slot bị cắt im lặng xuống trần của kiểu (`intro_text` khai 300, trần
`text` lúc đó là 200), nay trần `text` là 300 và chuyện trần thắng được ghi rõ.
Thứ hai, `slot_contract` không hiểu được từng để lại 10 token `{{slot:…}}` nguyên
văn trên trang, đúng cái mà comment trong code nói là không xảy ra: `apply()` gọi
`declared()`, hàm này trả rỗng khi contract lạ, nên `apply()` thoát sớm và trả
markup chưa đụng tới. Tách `supported()` khỏi `normalize()` mới sửa được.

**Kiểm thật qua đúng nút import, 2026-10-09.** Tám slot điền bằng tiếng Việt qua
dialog trong `samples-panel.tsx`, trong đó intro cố tình chứa `"` và `-->`. Kết
quả: draft tạo xong còn 0 token, `serialize_blocks(parse_blocks($x)) === $x`,
`tel:+842838221234` suy ra từ `+84 (28) 3822 1234`, Gutenberg mở không một cảnh
báo invalid block, front-end render đúng và `generate_for_post()` từ cache lạnh ra
7750 byte CSS khớp 13 trong 17 `blockId` của trang.

Một chỗ phải nói rõ: **lần save đầu tiên trong Gutenberg có đổi byte của
`post_content`**, 13376 xuống 12764. Không phải do slot. Đó là chuẩn hoá sẵn có
của editor: attribute đúng bằng default bị bỏ, thứ tự key xếp lại theo thứ tự khai
trong JS, và plugin tự đúc lại `blockId` mỗi lần mở. Một draft dựng thẳng từ markup
xuất xưởng, không đi qua slot, đổi y hệt (12796 xuống 12311). Save lần hai giống
save lần một từng byte trừ `blockId`. Giá trị slot không suy suyển qua các lượt
save, kể cả `\u0022` và `\u002d\u002d\u003e`.

## Giai đoạn 3 · Ability ghi

18. ✅ `includes/mcp/class-draft-writer.php`: wrapper quanh
    `Content_Importer::import()` ghim `post_status = draft`, và **không** để text
    do caller cung cấp đi qua đường `kses_remove_filters()` mà importer đang dùng
    (xem `class-content-importer.php` ~dòng 78). Chỉ nhận media ID đã tồn tại và
    caller có quyền. Validate toàn bộ request trước khi ghi.
19. ✅ Idempotency key theo user + operation, lưu transient có TTL. `find_existing()`
    hiện key theo preset nên nó trả lời "preset này từng import chưa", không phải
    "request này xử lý chưa". Nói rõ nó tương tác thế nào với hành vi "mở bản cũ
    hay import bản mới" của admin.
20. ✅ `flexa/list-presets`, `flexa/get-preset-schema`, `flexa/create-page-draft`.
21. ✅ Giới hạn rate và kích thước request, có số cụ thể. Toggle write riêng, mặc
    định tắt kể cả khi MCP đã bật.

**Xong khi**: client thật chạy trọn luồng discover, đọc, tạo draft; mọi cố gắng
ép `publish` bị từ chối; gửi lại cùng idempotency key trả về đúng post cũ chứ
không tạo bản thứ hai.

**Mục 18 trả lời bằng ba lớp, không bằng một lần lọc.** Thứ nhất, caller không
bao giờ gửi markup: nó gửi giá trị slot, và giá trị slot chỉ đi vào **giá trị
attribute** (cơ chế chốt ở mục 13), nên không có đường nào để chữ của nó bị đọc
như HTML. Thứ hai, giá trị thô chứa `<` hoặc `>`, hoặc ký tự điều khiển, bị **từ
chối thẳng** kèm lời giải thích, chứ không lọc rồi ghi. Chỗ đặt kiểm này quan
trọng: đặt sau `Preset_Slots::values()` thì thành diễn kịch, vì tới đó
`sanitize_text_field()` đã dọn sạch và sẽ không còn gì để tìm. Thứ ba, cả request
được validate xong mới tới lần ghi đầu tiên, nên một request sai không để lại
draft nửa vời. `Draft_Writer` là chỗ duy nhất gọi importer từ đường MCP; ability
không gọi `Content_Importer::import()` trực tiếp, đúng như mục rủi ro yêu cầu.

**Mục 19, idempotency key là của request, không phải của preset.** Record lưu
trong transient, key băm theo `user + operation + idempotency_key`, nên hai user
gửi cùng một chuỗi không đụng nhau. Key do caller gửi sống một ngày; key tự suy
ra từ nội dung request (`auto:` + md5 của source, preset, title và slot đã ksort)
sống 5 phút, đủ cho một lần retry sau timeout mà không biến "gọi lại tháng sau"
thành "không tạo gì cả". Trước khi ghi, record được đặt sẵn ở trạng thái
`pending` 120 giây, nên hai request song song cùng key thì một cái nhận 409
`flexa_mcp_in_progress` thay vì cả hai cùng tạo page. Post đã bị trash hoặc xoá
coi như mất: key còn sống nhưng trả về draft mới.

Quan hệ với `find_existing()` của admin: hai hàm trả lời hai câu khác nhau.
`find_existing()` key theo preset, nên nó nói "preset này từng import trên site
chưa", đúng cho cái nút hỏi người dùng "mở bản cũ hay import bản mới". Record
idempotency nói "request này xử lý chưa". `flexa/list-presets` và
`flexa/get-preset-schema` có báo lại kết quả `find_existing()`, nhưng **chỉ như
thông tin** và chỉ khi user có `read_post` với post đó. Nó không bao giờ là lý do
từ chối: client tự quyết định tạo thêm hay không, và `create-page-draft` không
đọc nó.

**Mục 21, các con số.** 10 draft mỗi giờ và 120 lần discovery mỗi giờ, đếm theo
user và theo từng ability, cộng với trần kích thước: 16 KB cho cả request, 120 ký
tự cho title, 25 field và 8 KB mỗi field (của `Preset_Slots`), 50 preset cho một
lần listing. Cửa sổ là fixed window trong transient, hết giờ thì counter tự hết
hạn chứ không gia hạn theo mỗi lần gọi, nếu không thì trần sẽ thôi còn là trần
theo giờ. Chi phí đã biết của cách này: một burst vắt qua ranh giới hai cửa sổ có
thể đi gấp đôi trần trong vài phút. Chấp nhận, vì đây là trần chống tai nạn chứ
không phải chống tấn công. Trần listing có cờ `truncated` trong output, nên một
site nhiều preset biết là mình nhận bản bị cắt chứ không âm thầm nhận một phần.
Toggle write riêng, mặc định tắt kể cả khi MCP đã bật, và **cả ba ability** của
giai đoạn này nằm sau nó, kể cả hai ability chỉ đọc: chúng tồn tại để phục vụ
luồng ghi, bật chúng khi write đang tắt chỉ là mời client đi một vòng rồi bị từ
chối ở bước cuối.

`post_status` khai trong input schema là `enum: ['draft']`, nên cố ép `publish`
bị từ chối ngay ở tầng schema với đúng câu "input[post_status] is not draft",
không phải bị bỏ qua im lặng. Writer vẫn kiểm lại lần nữa
(`flexa_mcp_publish_refused`) cho đường gọi trực tiếp từ PHP.

**Kiểm thật qua client MCP, 2026-10-09**, chạy bằng account `editor` chứ không
phải admin. Discover ra đủ 5 ability với annotation đúng; `list-presets` trả 18
preset của ba source trong 19421 byte; `get-preset-schema` trả đủ 8 field của
contact-page với contract 1/1; `create-page-draft` tạo draft 7 slot, 13362 byte,
còn 0 token, `serialize_blocks(parse_blocks($x)) === $x`, `tel:+842838221234` suy
ra từ `+84 (28) 3822 1234`, tiếng Việt nguyên vẹn, và `generate_for_post()` từ
cache lạnh ra 7990 byte CSS khớp 13 trong 17 `blockId`, cùng hình dạng giai đoạn 2
đo được. Gửi lại cùng key trả về đúng post cũ với `reused: true` và không có post
thứ hai. Mọi đường từ chối đều kiểm: markup trong slot, slot lạ, email sai, vượt
độ dài, preset lạ, source lạ, key sai định dạng, 429 ở cả hai trần, 403
`flexa_mcp_cannot_create` cho user không có `edit_pages`. Trần listing kiểm bằng
cách hạ `MAX_PRESETS` xuống 3: trả 3 preset và `truncated: true`.

**Hai lỗi nữa do kiểm thật bắt được.** Thứ nhất, với một filter trên
`wp_insert_post_data` ép `post_status = publish`, chính `wp_update_post()` sửa
lưng của `hold_at_draft()` cũng bị ép theo, thế mà response vẫn báo "đã đặt về
draft" trong khi `status` trả về là `publish` kèm permalink công khai. Nay post
được đọc lại sau khi sửa và báo một trong hai cảnh báo khác nhau, còn `status`
báo ra là cái site đang có, không phải cái class vừa yêu cầu. Thứ hai,
`get_the_title()` trả `Liên hệ Bánh &#038; Cà Phê` cho title lưu là `Liên hệ Bánh
& Cà Phê`: nó chạy filter hiển thị, còn đây là API nên phải báo giá trị. Sửa cả ở
writer và ở `get-page-block-tree` của giai đoạn 1. Cùng chỗ đó, `modified_gmt`
của draft chưa từng publish là `0000-00-00 00:00:00` (WordPress copy post date
rỗng sang), nay quy đổi từ `post_modified`.

## Giai đoạn 4 · Hoàn thiện để phát hành

22. ✅ Activity log theo `flexa-plugin-activity-log`, sau khi đã kiểm xem
    observability của `mcp-adapter` ghi sẵn những gì để không trùng. Chỉ
    timestamp, user, ability, post ID, outcome, request ID. Không nội dung,
    không prompt, không token. Có retention và cleanup. Không mô tả nó như cơ chế
    rollback. Quyết định writer nằm trong module bị gate hay cạnh panel, vì khi
    module tắt thì không có gì để ghi.
23. ✅ Setup guide cho đúng một client đã verify, và compatibility matrix chỉ gồm
    client/transport mà ta phát biểu được yêu cầu, kèm hai protocol revision mà
    `mcp-adapter` 0.7.x hỗ trợ. Không quảng cáo tương thích phổ quát.
24. ✅ readme.txt: tính năng mới, tắt mặc định, cần WP 7.0 và `mcp-adapter`, giới
    hạn, mục privacy và data-flow, và nói thẳng rằng AI client do người dùng chọn
    sẽ nhận nội dung website qua các tool call được cho phép. Theo `wp-readme-txt`.
25. ✅ Changelog, bump `FLEXA_BLOCK_VER`, xác nhận `Requires at least`,
    `Requires PHP`, `Tested up to` không cần đổi vì module tự gate bên trong.
26. ✅ `makepot.sh`, Plugin Check, `phpcs.xml.dist`, `phpstan.neon.dist` sạch như
    baseline hiện tại.
27. ✅ Regression: site không bật thì bản update không đổi gì, kể cả số file load
    trên một request front-end.

**Xong khi**: ZIP build từ `release.sh` cài lên site sạch WP 7.1, bật module, chạy
trọn luồng với client thật, và trên site WP 6.4 bản update không gây notice hay
fatal nào.

Đã kiểm trên site dev WP 7.1.3: ZIP `release.sh` build ra giải nén cài được, Plugin
Check chạy trên đúng bản ZIP đó, và luồng với Claude Code đã chạy từ giai đoạn 3.
Còn lại hai việc thuộc QA phát hành, cần máy khác chứ không phải code: cài ZIP lên
một site WP 7.1 sạch hoàn toàn, và chạy bản update trên một site WP 6.4 thật.

**Mục 22, log là option chứ không phải bảng, và nó sống ngoài công tắc.**
Observability của `mcp-adapter` 0.7 không trả lời được câu hỏi này: handler mặc
định là null nên site không tự wire thì chẳng ghi gì, tên tool nó thấy luôn là
`mcp-adapter-execute-ability` (meta-tool, không nói được ability nào chạy), và nó
chỉ thấy call đi qua MCP trong khi một ability còn gọi được qua REST route của
core và từ PHP. Nên log này không trùng, nó ghi bốn outcome (`ok`, `refused`,
`denied`, `error`) bằng cách bám bốn hook của core chứ không bọc callback của
chính mình, vì hai outcome đáng ghi nhất (input sai schema, caller thiếu quyền)
không bao giờ chạm tới callback. Plugin này không có bảng và không có bộ máy
migration, thêm cả hai cho vài trăm dòng log là thay đổi cấu trúc lớn nhất của cả
module, nên chỗ chứa là một option không autoload, có trần 500 dòng và retention
30 ngày. Giá phải trả nói thẳng trong docblock: hai request MCP rơi đúng cùng một
khoảnh khắc có thể mất một dòng, site cần trail chắc chắn thì hook
`Activity_Log::HOOK` và tự ghi. Phần ghi nằm trong gate (`watch()` gọi từ trong
runtime), còn phần chứa, quét và đọc nằm cạnh panel, vì lúc người ta muốn đọc log
nhất là ngay sau khi vừa tắt module, và dòng cũ vẫn phải già đi rồi rụng.

**Mục 23, một client, một transport, hai protocol revision.** `docs/mcp-setup.md`
viết cho Claude Code vì đó là tổ hợp duy nhất đã chạy thật với plugin này. Bảng
compatibility chỉ có ba dòng đã kiểm (Claude Code trên `2025-11-25`, curl trên cả
`2025-11-25` và `2026-07-28`), kèm đúng những gì mỗi revision đòi: revision cũ
cần session id và header `MCP-Protocol-Version`, revision mới không có session
nhưng bắt `params._meta` và một bộ header `Mcp-*`. Không có dòng nào nói "tương
thích mọi client MCP".

**Mục 24 và 25, readme nói thẳng dữ liệu đi đâu.** Mục `== Privacy and data flow ==`
viết bốn ý: dữ liệu đi tới đâu (tới AI client do chính người dùng chọn, không qua
máy chủ nào của Flexa), client nhận được gì, cái gì chặn nó lại, và site lưu lại
gì. Hai chi tiết dễ bị bỏ sót nhưng phải có: MCP Adapter còn phục vụ tool của
WordPress và của plugin khác mà toggle của Flexa không quản (trên site
WooCommerce, một tài khoản Editor vươn tới được tool sản phẩm và đơn hàng của
WooCommerce), và giới hạn theo giờ là 10 draft cùng 120 call cho mỗi tool preset,
không phải "120 call đọc" như bản nháp đầu viết sai. Version lên 1.0.17 ở cả
header và `FLEXA_BLOCK_VER`; `Requires at least` giữ 6.4 và `Requires PHP` giữ
7.4 đúng như mục 25 dự đoán, vì module tự gate bên trong.

**Mục 26, Plugin Check có hai cái bẫy không nằm trong tài liệu của nó.** Thứ nhất,
`Direct_File_Access_Check` chỉ đọc 50 dòng đầu file, và nó cắt trước khi bỏ
comment, nên một file docblock dài đẩy guard `ABSPATH` xuống dòng 52 là đủ để báo
`missing_direct_file_access_protection`. Cách sửa không phải rút ngắn lý lẽ mà là
chuyển phần dài xuống docblock của class, chỗ nó vốn thuộc về. Thứ hai,
`WP_Functions_Compatibility_Check` chỉ chấp nhận `function_exists()` nằm **cùng
file** với lời gọi, nên gate ở `flexa-block.php` không cứu được
`class-mcp-manager.php`; hai hàm đăng ký giờ tự hỏi lại, và đó cũng là câu trả
lời đúng chỗ cho người đọc file đó. Sau khi sửa, chạy Plugin Check trên đúng bản
ZIP chỉ còn một warning có sẵn từ trước (`DynamicHooknameFound` của
`FormFlow_Promo::RENDERED_ACTION`). phpcs giữ nguyên 12 lỗi cũ trên tám file MCP,
phpstan giữ nguyên 4 lỗi baseline, `makepot.sh` chạy sạch.

**Mục 27, đo bằng hai bản chạy cạnh nhau chứ không bằng suy luận.** Cách làm: một
git worktree ở `a529ede` (commit ngay trước giai đoạn 0) copy vào
`wp-content/plugins/zz-flexa-old`, một mu-plugin tạm đổi `option_active_plugins`
sang bản cũ khi URL có tham số, và `register_shutdown_function()` ghi
`get_included_files()` ra file. Hai lần đo đầu bắt được hai thay đổi mà đọc diff
không thấy. Một: `Import_Manager::init()` require mọi file engine ngay khi boot,
nên `class-preset-slots.php` thêm vào đó là +1 file cho **mọi** request front-end
kể cả site không bật module. Sửa bằng `Import_Manager::load_preset_slots()`, gọi
từ ba chỗ thật sự cần (`Import_Registry::sources()` là nút thắt mọi source đi
qua, kể cả source của Pro; `Content_Importer::import()`; và
`Write_Abilities::contribute()`). Hai: `Activity_Log::init()` gọi
`ensure_scheduled()` trên mọi request admin, nên một site không bao giờ bật module
vẫn bị bản update cắm thêm một cron event hàng ngày. Giờ dòng đầu tiên mới lên
lịch, và `collect()` tự gỡ lịch khi log rỗng, nên log vẫn sống lâu hơn công tắc
còn event thì không sống lâu hơn log. Sau hai lần sửa, ba cặp đo liên tiếp cho
cùng con số (2765 file, danh sách trùng từng dòng) và HTML trang chủ hai bản khớp
từng byte sau khi chuẩn hoá đường dẫn plugin.

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
