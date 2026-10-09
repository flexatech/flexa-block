# MCP Module

> ⬜ chưa bắt đầu (19) · 🧪 đang làm · ✅ xong
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

1. ⬜ `includes/admin/class-mcp-settings.php`: option `flexa_block_mcp`, DEFAULTS,
   get/save, sanitize, REST `flexa-block/v1/mcp` (GET/POST, `manage_options`).
   Permission callback viết tại chỗ, không gọi `Admin::rest_permission()`, để sau
   này tách file ra plugin riêng không phải sửa.
2. ⬜ Guard WP 7.0 ở save path. Chặn cả form post, `update_option()` từ WP-CLI, và
   payload settings import. Chọn một tầng (sanitize callback hay
   `pre_update_option_flexa_block_mcp`) rồi ghi lý do vào docblock.
3. ⬜ Key `mcp` trong boot payload `flexaBlockAdmin`
   (`includes/admin/class-admin.php` ~447):
   `{ supported, minWp, enabled, read, write, adapter: { active, version }, endpoint, restUrl }`.
   Panel đọc từ đây nên vẽ được cả khi module đang tắt.
4. ⬜ `includes/mcp/class-mcp-manager.php`: `init()`, soft-detect `mcp-adapter`,
   hook `wp_abilities_api_init`, filter `flexa_block_mcp_abilities` (tên đề xuất)
   để sau này plugin Flexa khác góp ability. Chưa đăng ký ability nào.
5. ⬜ `flexa-block.php`: require `class-mcp-settings.php` trong admin context
   (luôn luôn), và require `includes/mcp/` **chỉ khi** option bật và
   `version_compare( get_bloginfo('version'), '7.0', '>=' )`. Flag đã lưu không
   được tin một mình: site có thể bật ở 7.0 rồi restore backup về 6.9.
6. ⬜ `src/admin/mcp-panel.tsx` + nav entry và view branch trong
   `src/admin/index.tsx`. Nav item **luôn hiện**, kể cả WP < 7.0. Bốn trạng thái:
   - WP < 7.0: toggle disabled, một câu lý do, không request nào.
   - WP 7.0+, tắt: công tắc và mô tả ngắn việc bật sẽ mở ra cái gì.
   - Bật, thiếu `mcp-adapter`: giải thích adapter, và nói rõ ability vẫn gọi được
     qua route abilities của core.
   - Bật, có adapter: endpoint, config copy được, setup guide, toggle read/write,
     phạm vi user và nội dung, activity.
7. ⬜ Panel save tường minh, không tham gia auto-save debounced của app, và hành
   động bật có confirm.
8. ⬜ `uninstall.php`: xoá `flexa_block_mcp` và transient của nó.

**Xong khi**: site 6.4 thấy nav item với toggle disabled; bật trên 7.1 thì module
load, `discover-abilities` của adapter không trả ability Flexa nào; `wp option
update flexa_block_mcp` để bật trên 6.9 bị từ chối; lưu settings chung không đụng
option mới và lưu option mới không flush CSS cache; request front-end của site
chưa bật không load thêm file nào.

## Giai đoạn 1 · Ability chỉ đọc

9. ⬜ Helper dùng chung cho ability: schema, permission callback, `meta.mcp`,
   availability. Tránh lặp 5 lần.
10. ⬜ `flexa/get-design-context`: palette và dark mode từ
    `class-global-styles.php` + `class-dark-mode-settings.php`, danh sách block
    khả dụng từ `Block_Manager`. Chỉ ngữ cảnh thiết kế. Không dump
    `flexa_block_settings`, không chẩn đoán môi trường.
11. ⬜ `flexa/get-page-block-tree`: `parse_blocks()` + `current_user_can(
    'read_post', $id )`. Output phân biệt rõ dữ liệu serialize và attribute đã
    resolve. Có giới hạn kích thước payload.
12. ⬜ Toggle read riêng: tắt read thì hai ability trên không được đăng ký.

**Xong khi**: một MCP client thật discover thấy đúng 2 tool, đọc được một page,
và trả về rỗng với page của user khác mà caller không có quyền đọc.

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
- Tầng nào enforce guard WP 7.0 (mục 2).
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
- **`Content_Importer::import()` bỏ KSES** (`kses_remove_filters()`). An toàn cho
  nút admin với markup do plugin bundle, không an toàn cho đường MCP. Mục 18 tồn
  tại chỉ để chặn chuyện này; đừng gọi importer trực tiếp từ ability.
