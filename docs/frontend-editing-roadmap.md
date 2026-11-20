# Sửa ngoài Front-End: nâng cấp

> ✅ xong (29) · 🧪 cần kiểm tra lại (2) · ⬜ chưa bắt đầu (0)
> · Cập nhật: 2026-11-19 · Nhánh: `feat/frontend-editing` (tách từ `feat/mcp-module-phase-0`)
>
> Phạm vi block/trường đang sửa được: [`inline-editing-coverage.md`](inline-editing-coverage.md).
> File này là thứ tự thi công cho phần nâng cấp: lưu an toàn, chống ghi đè,
> không để mất chữ đang gõ, lịch sử gọn và hoàn tác được, sửa link, rồi mở rộng
> sang **hình ảnh** và **icon**. Làm lần lượt từng mục; mục nào xong thì đổi ⬜
> thành ✅ và cập nhật số đếm ở trên.

---

## Hiện trạng

`includes/class-inline-editor.php` + `src/frontend/inline-editor.ts`. Người có quyền
click vào chữ trên trang, sửa trong `contenteditable`, mỗi trường lưu một request
`POST flexa-block/v1/inline-edit` (`postId`, `blockId`, `blockName`, `attr`,
`index`/`subindex`/`key`, `value`). 35 loại block, registry mở qua filter
`flexa_block_inline_editable`. Mỗi lần sửa ghi một comment "note" trên bài.

Những chỗ cần sửa, theo mức độ nghiêm trọng:

1. **Lưu bằng round trip cả trang.** `handle_save()` gọi `parse_blocks()` rồi
   `serialize_blocks()` toàn bộ `post_content` để đổi một attribute. PHP decode
   attribute thành array nên không phân biệt `{}` với `[]`: sửa một chữ có thể
   đổi mọi object rỗng của mọi block trên trang thành list rỗng. Đây đúng là rủi
   ro roadmap MCP ghi ở mục `get-page-block-tree`, và MCP đã có lời giải
   (`Block_Patcher`).
2. **Block không nằm trong bài viết.** Block trong header/footer (template part)
   hoặc synced pattern không nằm trong `post_content` của trang đang xem. Nếu vẫn
   được đánh dấu sửa được thì lưu sẽ báo "block không còn trong bài" (cần kiểm
   lại hành vi thực tế, mục 6).
3. **Không chống ghi đè.** Không so `post_modified_gmt`, không xem
   `wp_check_post_lock()`. Một người sửa ngoài front-end trong khi người khác đang
   mở editor: ai lưu sau thắng, không ai được báo.
4. **Mất chữ đang gõ mà không báo.** Đang sửa một trường mà bấm sang trường
   khác thì `startEdit()` gọi `endEdit( true )` và bỏ bản đang sửa
   (`src/frontend/inline-editor.ts`, comment "discard that unsaved edit"). Bấm
   link, reload hay đóng tab cũng mất, vì không có `beforeunload`.
5. **Lịch sử rối.** Mỗi lần lưu một trường là một revision và một comment
   "note" (`log_edit()`). Sửa 20 chỗ trên một trang để lại 20 revision và 20
   comment, đẩy số comment trên thanh admin lên, và không có cách hoàn tác nhanh.
6. **Chỉ sửa được chữ.** Không sửa được link (đổi được nhãn nút nhưng không đổi
   được nút dẫn đi đâu), hình ảnh hay icon. Coverage xếp `flexa/image`,
   `flexa/icon`, ảnh/icon trong các block khác vào "Không áp dụng".
7. **Ghi thẳng lên trang đang chạy.** Đúng ý với người sửa trang của chính mình,
   nhưng không có lựa chọn "lưu để duyệt" cho site cần quy trình duyệt.

---

## Giai đoạn 1 · Lưu an toàn (làm trước, sửa lỗi có thể làm hỏng dữ liệu)

1. ✅ Đưa `Block_Patcher` ra khỏi phạm vi MCP: giờ là
   `includes/class-block-patcher.php`, namespace `Flexa\Block`, nạp cùng các file
   lõi nên luôn có sẵn cho inline editor. Lỗi của nó đổi sang mã trung tính
   `flexa_block_*` với thông báo chỉ nói chuyện gì xảy ra; `Page_Editor` của MCP
   bọc lại thành `flexa_mcp_*` kèm lời khuyên "đọc lại bằng
   flexa/get-editable-fields", nên API của MCP không đổi (kiểm trên site: lỗi
   block lạ vẫn trả `flexa_mcp_unknown_block`, sửa thật vẫn chạy).
2. ✅ Viết lại phần ghi của `handle_save()`: tìm block bằng
   `Block_Patcher::locate()`, kiểm tên khớp `blockName`, tính giá trị mới trên
   attribute decode dạng object (`with_field()`, default lấy từ `block.json` qua
   `default_attr()`), rồi `Block_Patcher::replace()`. Bỏ `flatten()` và
   `default_attr_array()`. Mã lỗi và thông báo giữ như cũ
   (`flexa_block_not_found`, `flexa_bad_index`), thêm `flexa_block_duplicate`
   (409) khi hai block trùng `blockId`. Khác cũ một chỗ có chủ ý: ô bảng 2 chiều
   chỉ được ghi vào cột đã có hoặc cột kế tiếp; cột xa hơn bị từ chối, vì để hở
   một chỉ số sẽ biến list thành object khi encode.
   Kiểm trên site (trang 2760) qua đúng route `inline-edit`: heading (HTML được
   kses, `<script>` bị bỏ), FAQ (repeat), countdown `labels` (object, lấy từ
   default), comparison table (ô), icon list (repeat, lấy từ default): mỗi lần lưu
   chỉ đổi đúng một dòng delimiter. Chỉ số sai, cột hở, block lạ, sai tên block
   đều bị từ chối và không ghi gì.
3. ✅ Test giá trị HTML trong `BlockPatcherTest`: định dạng inline, link có
   `&amp;` và `target`, `<br>`, `-->` / `<!--`, dấu nháy và `\`. Mỗi giá trị đọc
   lại đúng nguyên văn, và chuỗi JSON trong delimiter không còn `<`, `>` hay `--`
   thô. Thêm test HTML trong một phần tử list không đụng phần tử bên cạnh.
4. ✅ `handle_save()` tắt KSES đúng một lần ghi (`kses_remove_filters()`), rồi
   bật lại bằng `kses_init()` (không phải `kses_init_filters()`, để user có
   `unfiltered_html` không bị bật KSES cho phần còn lại của request). Giá trị mới
   đã qua allowlist của trường; phần còn lại là nội dung đang lưu. Filter
   custom CSS của core 7.0 (`wp_strip_custom_css_from_blocks`) vẫn chạy: nó tự
   ghi theo kiểu splice, chỉ đụng block có `style.css`.
5. ✅ Test hồi quy trên site, đọc nội dung thật qua endpoint revisions (xem rủi
   ro đầu tiên bên dưới): một trang chứa đủ 35 block của registry và một
   container có `{}` và `[]`; lưu lần lượt 73 trường (chữ, repeater, object, ô
   bảng) qua route `inline-edit`. Cả 73 lần đều lưu được, mỗi lần chỉ đổi đúng
   một dòng delimiter của đúng block đó, `{}` và `[]` còn nguyên đến cuối.
6. ✅ **Block không nằm trong bài.** `annotate()` chỉ gắn `data-flexa-edit-*`
   khi `blockId` có trong `post_content` của chính bài đang xem
   (`Block_Patcher::block_ids()`, đọc một lần mỗi request). Block từ template
   part, synced pattern hay bài khác trong query loop vẫn render bình thường,
   không có viền sửa. Có unit test cho `block_ids()`, kể cả block lồng nhau và
   synced pattern chỉ là tham chiếu. **Chưa kiểm trên trình duyệt** (front-end
   chỉ render toolbar cho người đăng nhập bằng cookie). Mục 31 (làm sớm) mở
   rộng điều này sang template part và synced pattern.

## Giai đoạn 2 · Chống ghi đè

7. ✅ **Phiên bản nội dung thay cho `post_modified_gmt`.** Lúc render, mỗi block
   sửa được mang `data-flexa-edit-version` = md5 của `post_content` đang lưu ở
   nơi chứa nó (trang, template part hoặc pattern; tính một lần cho mỗi nơi).
   Lưu gửi kèm `version`; server so với md5 của nội dung hiện tại, lệch thì trả
   409 `flexa_inline_stale` và không ghi gì. Lưu xong trả `version` mới (đọc lại
   từ DB), front-end gắn vào mọi block cùng nơi chứa để lần sửa sau trên cùng
   trang không tự chặn. Không dùng `post_modified_gmt` như dự định ban đầu: nó
   chỉ chính xác tới giây (hai lần lưu trong một giây trông như nhau) và đổi cả
   khi chỉ meta thay đổi. `version` là tuỳ chọn ở phía server để script cũ còn
   trong cache vẫn lưu được, chỉ là không được kiểm.
8. ✅ Từ chối khi người khác đang mở chính nơi chứa đó trong block editor
   (`wp_check_post_lock()` trên trang, template part hoặc pattern), trả 409
   `flexa_inline_locked` kèm tên người đó và thời gian chờ ước tính ("khoảng
   2–3 phút", tính từ `wp_check_post_lock_window`, mặc định 150 giây): khoá còn
   hiệu lực ngần ấy sau lần gia hạn cuối, và editor còn mở thì Heartbeat cứ gia
   hạn, nên thông báo nói "sau khi họ đóng editor" chứ không đếm ngược. Không tự
   lấy khoá. Đã kiểm trên site bằng hai tài khoản: khoá của tài khoản khác trả
   409, thông báo đúng tên người giữ khoá và khoảng chờ, chữ đang gõ vẫn còn
   nguyên trong trường sửa.
9. ✅ Gộp vào mục 7. So giá trị đang hiển thị với giá trị đang lưu không làm
   được: render đã chạy kses, entity và markup của block lên giá trị, nên hai
   bên khác nhau cả khi không có gì thay đổi, sẽ báo xung đột sai liên tục. Phiên
   bản nội dung của mục 7 chính xác tới từng byte, nên bao luôn trường hợp hai
   tab cùng sửa một trường.
10. ✅ Khi lưu bị từ chối (409 hay bất kỳ lỗi nào), toolbar giữ nguyên chế độ sửa
    và chữ người dùng vừa gõ, hiện thông báo của server (nội dung đã đổi: copy
    chữ rồi tải lại trang; có người đang mở editor: thử lại sau).
    Kiểm trên site (trang 2914): không gửi version vẫn lưu được; sửa trường thứ
    hai bằng version lần trước trả về: lưu được; dùng lại version cũ, version
    bịa, hoặc sau khi trang bị sửa qua REST: 409, không ghi gì.

## Giai đoạn 3 · Hỏi trước khi rời đi khi còn thay đổi chưa lưu

"Có thay đổi chưa lưu" nghĩa là trường đang sửa có giá trị khác `originalHTML`
lúc bắt đầu sửa (so sau khi chuẩn hoá khoảng trắng, để click vào rồi click ra
không bị tính là đã sửa), hoặc một lần lưu đang chạy dở.

> **Đã nghiệm thu mục 11–15** (2026-11-19): chạy tay trên site thật (Chrome,
> Firefox, mobile) và chạy tự động 14 kiểm tra trên Chromium, Firefox, WebKit
> (chuyển trường, link, form, Escape, dấu `*`, link/Huỷ khi đang lưu). Lúc kiểm
> sửa ba lỗi: bấm vào chữ đang sửa nằm trong thẻ link làm rời trang; bấm link
> hay Escape khi đang lưu dở khôi phục chữ cũ dưới một request vẫn có thể thành
> công (nay chờ request xong, `inflight`); chữ gõ sau dấu `*` của field bắt buộc
> bị `currentValue()` bỏ mất (nay dấu `*` không sửa được, và `beforeinput` đưa
> con trỏ ra sau nó vì Firefox nhốt con trỏ trong đó). Chưa kiểm Safari thật.

11. ✅ Theo dõi trạng thái `dirty` trong `inline-editor.ts`: hàm `isDirty()` so
    `currentValue()` với giá trị gốc. Lưu thành công hoặc bấm Huỷ thì hết dirty.
12. ✅ **Bấm sang trường khác khi đang dirty**: không bỏ bản đang sửa nữa, mà hiện
    hộp hỏi ngay trên toolbar với ba nút: **Lưu** (lưu rồi chuyển sang trường
    mới), **Bỏ thay đổi** (khôi phục rồi chuyển), **Tiếp tục sửa** (ở lại trường
    cũ). Không dùng `window.confirm()` vì nó chỉ có hai nút và không dịch được
    theo plugin. Lưu thất bại (ví dụ 409 ở giai đoạn 2) thì ở lại trường cũ, giữ
    nguyên chữ.
13. ✅ **Bấm link trong trang khi đang dirty** (menu, nút, link bất kỳ không phải
    trường đang sửa): bắt sự kiện `click` ở pha capture trên `document`, chặn điều
    hướng, hiện cùng hộp hỏi ở mục 12. Chọn Lưu hoặc Bỏ thay đổi thì mới đi tới
    `href` đã chặn (giữ `target="_blank"` và phím Ctrl/Cmd như trình duyệt). Áp
    dụng cả cho `submit` của form trên trang.
14. ✅ **Reload, đóng tab, gõ địa chỉ mới, nút Back** khi đang dirty hoặc đang lưu:
    gắn `beforeunload` (`event.preventDefault()` + `returnValue`). Trình duyệt chỉ
    cho hiện **hộp thoại mặc định của nó** ("Rời khỏi trang? Thay đổi có thể không
    được lưu"), không cho đổi chữ và không cho thêm nút Lưu; đây là giới hạn của
    trình duyệt, không làm khác được. Chỉ gắn listener khi dirty và gỡ ngay khi hết
    dirty, để không cản bfcache và không hỏi vô cớ.
15. ✅ Phím **Escape** khi đang dirty: hỏi "Bỏ thay đổi?" thay vì khôi phục ngay.
    Không dirty thì thoát ngay như hiện tại. Thêm chuỗi dịch mới vào `boot.i18n`
    (`Inline_Editor::enqueue()`), kiểm tra bằng tay trên Chrome, Firefox, Safari:
    bấm trường khác, bấm link menu, reload, đóng tab, Back, Escape.
    Đạt trên Chrome, Firefox, mobile (tay) và Chromium/Firefox/WebKit (tự động):
    dirty guard, chuyển trường với đủ ba lựa chọn, link, submit form, Escape,
    lưu rồi reload, xung đột 409 giữ nguyên chữ và không có lỗi console ngoài
    response 409 có chủ đích.

## Giai đoạn 4 · Lịch sử gọn và hoàn tác

16. ✅ **Gộp revision theo phiên sửa.**
    `Inline_Revision_Session` giữ phiên theo
    đúng nơi lưu + user trong transient, cửa sổ trượt mặc định 10 phút và đổi được
    qua `flexa_block_inline_revision_session_window`. Lần lưu đầu ép core tạo một
    revision bất biến **trước khi sửa**, kể cả revision mới nhất đang giống nội
    dung hiện tại; các lần lưu tiếp theo dùng lại mốc đó. Trong đúng lần gọi
    `wp_update_post()` của inline editor, cả filter revision chung và theo post
    type chỉ trả 0 cho đúng nơi đang lưu, rồi luôn được tháo trong `finally`, nên
    Block Editor và bài khác không bị ảnh hưởng. Revision được gắn meta nguồn để
    mục 17 có thể nhận diện; revisions bị tắt thì giữ nguyên hành vi core. Request
    không đổi nội dung không tạo revision, không ghi log. Có 6 unit test cho mốc
    đầu phiên, lưu lặp, tách user, hết hạn, revisions bị tắt và đường lưu lỗi;
    toàn bộ 1.520 test / 4.327 assertion qua. Đã kiểm live: lần lưu đầu tăng đúng
    một revision (nội dung trước khi sửa), nhiều lần lưu trong 10 phút không tăng
    thêm, hết phiên tạo mốc mới, lưu từ Block Editor vẫn tạo revision bình thường,
    bài khác và filter không bị ảnh hưởng. Lúc kiểm sửa một lỗi: meta nguồn bị
    `add_post_meta()` chuyển sang bài cha thay vì gắn vào revision; nay dùng
    `add_metadata()` (stub test cũng đã cập nhật). Đổi user và revisions bị tắt
    mới có unit test, chưa chạy live.
17. ✅ **Log sửa ngoài front-end, có tab riêng.** Thay comment "note" của
    `log_edit()` (chỉ một dòng chữ, mỗi giá trị cắt còn 80 ký tự) bằng bản ghi
    có cấu trúc, và thêm một panel/tab **Lịch sử sửa ngoài front-end** trong block
    editor để xem lại. Mỗi bản ghi gồm:
    - **Ai, lúc nào:** user ID, tên hiển thị lúc sửa (giữ lại cả khi tài khoản bị
      xoá), thời điểm theo GMT.
    - **Sửa gì, ở đâu:** tên và `blockId` của block, trường (`attr`, kèm
      `index`/`subindex`/`key` nếu là repeater, object hay ô bảng), và nơi lưu
      (trang, template part hay pattern, kèm `targetId`).
    - **Trước và sau:** giá trị cũ và giá trị mới, giữ nguyên HTML đã qua allowlist.
      Giá trị quá dài thì cắt ở một ngưỡng lớn (không phải 80 ký tự) và gắn cờ
      "đã cắt". Dùng làm dữ liệu cho mục 18, nên không được rút gọn thành chuỗi
      hiển thị.
    - **Liên kết revision:** ID revision mốc của phiên (mục 16), để panel có nút
      "Xem bản trước khi sửa" trỏ thẳng vào màn so sánh revision.

    Nơi lưu: post meta của nơi chứa (trang, template part hoặc pattern) có giới
    hạn số dòng, dòng cũ nhất bị bỏ trước; hoặc dùng chung cơ chế activity log của
    module MCP nếu nó đã có sẵn các trường trên. Panel hiển thị theo thứ tự mới
    nhất trước, lọc được theo người sửa và theo block, chỉ cho người có quyền sửa
    nơi đó xem. Log của block trong template part hay pattern nằm ở chính nơi đó
    chứ không chỉ ở trang đang xem, vì người mở pattern trong editor cần thấy ai đã
    sửa nó. Giữ filter để site nào vẫn muốn comment thì bật lại, và có bước dọn các
    comment "note" cũ (tuỳ chọn, không tự xoá).

    **Đã làm:** `Inline_Edit_Log` (`includes/class-inline-edit-log.php`) ghi một
    bản ghi mỗi lần lưu vào post meta `_flexa_block_inline_edit_log` của nơi chứa
    (tối đa 200 dòng, đổi qua `flexa_block_inline_edit_log_limit`; giá trị giữ
    nguyên đến 20.000 ký tự rồi cắt kèm cờ `truncated`, đổi qua
    `flexa_block_inline_edit_log_value_limit`). Bản ghi có id, thời điểm GMT, user
    ID và tên lúc sửa, block, `blockId`, `attr`/`index`/`subindex`/`key`, nơi lưu,
    trang đang xem, giá trị trước/sau và ID revision mốc của phiên
    (`Inline_Revision_Session::baseline_id()`). Route `GET
    flexa-block/v1/inline-edit-log?postId=` chỉ cho người có `edit_post` trên bài
    đó. Panel **Front-end edits** (`src/editor/inline-edit-log.tsx`, nạp cho mọi
    bài sửa được): mới nhất trước, lọc theo người và theo block, "Show more",
    liên kết "See the version from before" tới màn so sánh revision. Comment
    "note" cũ nay tắt mặc định, bật lại bằng filter
    `flexa_block_inline_edit_log_as_comment`. Có 8 unit test (1.528 test / 4.350
    assertion qua). Kiểm trên site qua `handle_save()`: hai lần sửa tạo hai bản
    ghi đúng trước/sau và cùng revision mốc, lưu không đổi không tạo bản ghi,
    không tạo comment, meta không lộ qua REST, khách và subscriber bị từ chối.
    **Tab Edit log trong admin (Flexa Block → Edit log):** xem nhanh mọi lần sửa
    của cả site (ai, lúc nào, ở đâu, trước/sau, liên kết sửa/xem trang và bản
    revision), lọc theo người và block, phân trang 20 dòng. Route `GET
    flexa-block/v1/inline-edit-log/all` chỉ cho `manage_options`. **Tự xoá sau 30
    ngày** (đổi bằng filter `flexa_block_inline_edit_log_retention_days`, 0 là không
    hết hạn): dọn ngay khi ghi một bản ghi mới, và bằng cron hằng ngày
    `flexa_block_inline_edit_log_sweep` (xoá cả log rỗng, tự gỡ lịch khi không còn
    log nào, gỡ khi tắt plugin; gỡ plugin thì xoá hết meta). Kiểm trên site: route
    trả 200 cho admin, 403 cho subscriber, 401 cho khách; lọc người/block và phân
    trang đúng; bản ghi 45 ngày tuổi bị sweep xoá, bản mới còn; lịch cron gỡ khi
    hết log. Tab này chưa xem trên trình duyệt. Sau lần xem đầu: giờ hiển thị theo
    múi giờ và định dạng ngày của site (`time_display`, không theo trình duyệt);
    Trước/Sau tách thành hai cột (cả bảng admin và panel trong editor); tiêu đề
    trang giải mã `&#8211;`; tab Edit log và panel trong editor chỉ hiện khi sửa
    ngoài front-end đang bật (Editing → master switch).
    Cột Sau in đậm các từ mới thêm so với cột Trước (so sánh theo từ,
    `src/shared/text-diff.tsx`, dùng ở cả tab admin và panel editor): từ thêm vào
    in đậm trên nền xanh, từ bị bỏ ở cột Trước gạch ngang trên nền đỏ. Tab admin
    dạng thẻ (mỗi lần sửa một thẻ: avatar, người, giờ, block · trường, trang, hai
    khung Trước/Sau mỗi bên nửa chiều rộng, liên kết so sánh revision) thay cho
    bảng; dưới 960px hai khung xếp dọc. Có ảnh đại diện (`get_avatar_url`, rơi về
    chữ cái), nhãn vai trò tô màu theo role (vai trò lúc sửa, lưu trong bản ghi),
    bộ lọc người/block dạng nút + menu, và ô tìm kiếm (server tìm không phân biệt
    hoa thường trong chữ Trước/Sau không tính HTML, tên trang, người, block,
    trường; chờ 400 ms sau khi gõ; chữ khớp tô vàng).

    **Chưa làm / chưa kiểm:** panel chưa xem trong block editor thật; bước dọn
    comment "note" cũ chưa có (comment cũ không có dấu nhận diện nên không tự xoá
    được an toàn); chưa dùng chung activity log của MCP (chọn post meta).
18. ✅ **Hoàn tác ngay sau khi lưu.** Lưu xong toolbar hiện "Đã lưu · Hoàn tác"
    trong vài giây. Hoàn tác gửi lại giá trị cũ của đúng trường đó (lấy từ bản ghi
    log ở mục 17, server trả về `before` khi lưu), đi qua cùng các kiểm tra của
    giai đoạn 1–2, nên không hoàn tác đè lên thay đổi của người khác. Bản hoàn tác
    cũng là một dòng log mới, không xoá dòng cũ.

    **Đã làm:** lưu thành công trả thêm `before` (giá trị cũ) và `logId`. Toolbar
    hiện "Saved · Undo" trong 10 giây (chỉ còn thông báo và nút Undo), hết giờ hoặc
    bắt đầu sửa chỗ khác thì mất; chỉ hoàn tác được lần lưu gần nhất. Bấm Undo gửi
    lại `before` kèm `version` hiện tại và `undoOf`: là một lần lưu bình thường nên
    qua đủ kiểm tra quyền, khoá editor và phiên bản nội dung; nếu nội dung đã đổi
    thì server từ chối (409) và không ghi gì, toolbar nói lý do. Ghi log thành bản
    ghi `action: undo` trỏ tới bản ghi bị hoàn tác (nhãn "Undo" trong tab admin);
    `undoOf` giả hoặc không có trong log thì chỉ coi là lần sửa thường. Trường
    ghép từ nhiều phần tử (`join`) thì tải lại trang sau khi hoàn tác. Kiểm: 9 kiểm
    tra giao diện trên Chromium/Firefox/WebKit (hiện nút, gửi đúng giá trị, khôi
    phục chữ, hết hạn 10 giây, sửa chỗ khác thì mất nút, bị từ chối, không có log
    thì không có nút); trên site thật: lưu rồi hoàn tác khôi phục đúng thuộc tính,
    log có hai dòng đúng, sửa xen giữa bởi người khác thì hoàn tác bị từ chối và
    không ghi đè. Đã thử trên trang thật (2026-11-19).

## Giai đoạn 5 · Sửa link

19. ✅ **Sửa link.** Registry: loại trường `'type' => 'url'` gắn với trường chữ đi kèm (nút: `text`
    + `url`; heading: `content` + `url`; info box, CTA, banner: nút chính/phụ; icon
    list: `items[].link.url`). Khi đang sửa chữ của một phần tử có link, toolbar có
    thêm nút "Sửa link" mở popover nhập URL và tuỳ chọn mở tab mới nếu block có
    attribute đó. Server kiểm URL cùng luật với MCP (`http`, `https`, `mailto`,
    `tel`; từ chối `javascript:`), dùng chung hàm kiểm của `Preset_Slots`.

    **Đã làm (bản đầu, thuộc tính phẳng):** trường chữ trong registry có khoá
    `link` (`attr` URL, `target`, `anchor` là selector của thẻ `<a>`): heading
    (`content` → `url`), button (`text` → `url`), info-box (`buttonText` →
    `buttonUrl`), cta và banner (nút chính/phụ → `primaryUrl`/`secondaryUrl`).
    `Link_Url::clean()` (`includes/class-link-url.php`) là luật chung cho cả inline
    editor lẫn `Preset_Slots` của MCP: chấp nhận `http`, `https`, `mailto`, `tel` và
    địa chỉ không có scheme (`/lien-he`, `#muc`, `?trang=2`, `//cdn…`); từ chối mọi
    scheme khác kể cả ngụy trang (`JaVaScRiPt:`, xuống dòng/tab trong scheme,
    `java&#x0A;script:`, entity lồng nhau, `java script:`, `host:port` trần, ký tự
    điều khiển), dài quá 2.048 ký tự; địa chỉ rỗng nghĩa là gỡ link. Lưu qua đúng
    `handle_save()` với `link: true`, `value` là JSON `{url, newTab}`: ghi cả URL và
    thuộc tính target trong một lần (một dòng log, một lần Hoàn tác), thuộc tính
    bằng giá trị mặc định thì bỏ khỏi markup, không đụng kses; phiên bản nội dung,
    khoá editor, quyền và log đều như lưu chữ. Log ghi `kind: link` với trước/sau
    dạng `địa-chỉ (new tab)`. Giao diện: nút **Edit link** trên toolbar khi đang
    sửa chữ của phần tử có thẻ `<a>`; bấm thì kết thúc sửa chữ (hỏi Lưu/Bỏ/Tiếp tục
    nếu có thay đổi chưa lưu) rồi mở hộp thoại (địa chỉ, "Open in a new tab", gợi ý
    để trống là gỡ link; Esc/Huỷ/bấm nền để đóng). Lưu xong thẻ `<a>` trên trang
    đổi ngay và có "Saved · Undo". Server từ chối thì hộp thoại ở lại và nêu lý do.
    **Icon List (bổ sung):** khai báo `link` có khoá `item` (link nằm trong chính
    mục: `items[i].link.url` / `.target`, giữ nguyên `rel`), request gửi kèm `index`
    và `key: link`; nút Edit link chỉ bám vào thẻ `<a>` của đúng dòng đang sửa.
    Kiểm trên site thật: đổi link dòng 1 (bật tab mới, giữ `rel`), dòng 2 nguyên,
    Hoàn tác, thêm link cho dòng trước đó không có link qua server, từ chối
    `javascript:`, thiếu/sai chỉ số. **Thêm link / thêm icon ở chỗ chưa có:** khi
    sửa chữ có thể có link mà chưa có thẻ `<a>` (Heading, dòng Icon List), nút trên
    toolbar thành **Add link**; khi sửa chữ đi kèm một icon chưa có (khai báo icon có
    `with`: Button, nút Banner/CTA/Info Box, dòng Icon List) toolbar có **Add icon**.
    Lưu xong trang tự tải lại để block tự vẽ link/icon mới. Kiểm trên site thật: thêm
    link và icon cho dòng chưa có gì, thêm link cho heading, thêm icon cho button;
    render ra đúng thẻ `<a>` và icon. **Chưa làm:**
    heading chưa có link thì chưa thêm được (trang không có thẻ `<a>` để bám vào).
    Kiểm: 1.569 test PHP (gồm ~35 ca cho `Link_Url`); trên site thật qua
    `handle_save()` đổi URL, bật/tắt tab mới, gỡ link, hoàn tác, từ chối 5 kiểu
    ngụy trang, `{}`/`[]` của block khác còn nguyên; 19 kiểm tra giao diện trên
    Chromium, Firefox, WebKit; đã thử trên trang thật, gồm cả Firefox (2026-11-19).

## Giai đoạn 6 · Sửa hình ảnh

Attribute ảnh trong plugin **không** cùng một hình dạng. Image, Before / After,
Modal và Video Popup lưu `{ id, url, alt, width, height }`; Info Box, Team Member,
Testimonial và Timeline chỉ lưu `{ id, url, alt }`. Block editor lưu URL **ảnh gốc**
(không phải theo `imageSize`) cùng kích thước gốc và alt của attachment; riêng block
Image tính lại URL theo `id` và `imageSize` mỗi lần render, các block còn lại dùng
thẳng `url` đã lưu nên server phải ghi đúng `url`:

| Block | Attribute |
|---|---|
| Image | `image` |
| Info Box | `image` |
| Team Member | `image` |
| Testimonial | `authorImage` |
| Before / After | `beforeImage`, `afterImage` |
| Modal | `triggerImage` |
| Video Popup | `thumbnailImage` |
| Timeline | `items[].image` |

20. ✅ Registry: loại trường mới `'type' => 'image'` (cạnh trường chữ hiện có), khai
    báo `attr` (+ `repeat`/`key` cho Timeline) và `selector` của thẻ `<img>`. Filter
    `flexa_block_inline_editable` nhận loại mới để add-on dùng được.
21. ✅ Front-end: click ảnh mở **media modal của WordPress** (`wp.media`). Chỉ
    `wp_enqueue_media()` khi toolbar thật sự render cho user có quyền, không nạp
    cho khách. Chọn xong đổi `src` tạm để xem trước, rồi lưu.
22. ✅ Server **chỉ nhận ID attachment**, không nhận URL hay kích thước từ client.
    Kiểm: attachment tồn tại, là ảnh (`wp_attachment_is_image`), user đọc được
    (`read_post`), user có `upload_files`. Rồi tự dựng `url` / `width` / `height`
    từ attachment theo đúng `imageSize` của block, `alt` lấy alt của attachment
    nếu block đang trống. Dùng lại cách `Preset_Slots::clean_media()` đang kiểm.
23. ✅ Sửa alt text tại chỗ (ô nhập nhỏ trên ảnh) cho `image.alt`: là chữ, đi
    đường lưu chữ hiện có.

    **Đã làm (mục 20–23):** khác dự kiến ban đầu ở ba chỗ
    có chủ ý. (1) Registry có khoá `images` riêng cạnh `fields` (một block có thể
    chỉ có ảnh, như Image và Video Popup): `attr`, `selector` của `<img>`, `hit`
    (phần tử nhận click khi có lớp phủ), `shape` (`full` hay `basic`), `alt` (thuộc
    tính alt riêng, `altText` của Image), `size` (thuộc tính cỡ ảnh) và
    `repeat` + `key` cho ảnh trong item. Đã khai báo cho Image, Info Box, Team
    Member, Testimonial, Before/After (hai ảnh), Modal, Video Popup, Timeline.
    (2) Alt text không có chữ nào để sửa tại chỗ, nên ảnh và alt nằm chung một hộp
    thoại **Edit image** (xem trước, "Choose another image", ô Alt text); đổi ảnh
    và alt là một lần lưu, một dòng log, một lần Hoàn tác. (3) Server dựng dữ liệu
    giống hệt block editor (URL gốc, kích thước gốc nếu block lưu kích thước, alt
    của attachment) chứ không theo `imageSize`.

    Server: `Image_Source::describe()` (`includes/class-image-source.php`) chỉ nhận
    ID, kiểm là attachment ảnh, user có `upload_files` và `read_post`, rồi đọc URL,
    chiều rộng/cao, alt từ thư viện; không bao giờ tin URL hay kích thước từ trình
    duyệt. Đi qua `handle_save()` với `image: true` và `value` JSON `{id, alt}`
    (`id` null là giữ ảnh, chỉ đổi alt, và không cần `upload_files`): giữ các khoá lạ
    trong object ảnh, không thêm width/height cho block kiểu `basic`, `{}`/`[]` của
    block khác còn nguyên. Alt riêng (`altText` của Image) chỉ được cập nhật khi
    block đã dùng nó, để không đè alt của ảnh chọn sau. Phản hồi trả `after` (địa
    chỉ hiển thị đúng cỡ block, kích thước, alt) và `before` (`{id, alt}`) cho nút
    Undo; ảnh cũ không có ID thư viện thì không Undo được (`undo: false`). Log ghi
    `kind: image`, dạng `tên-file (#id) · alt: …`.

    Giao diện: rê vào ảnh thì viền nét đứt; bấm (kể cả qua lớp phủ, link hay
    lightbox của block, bắt ở pha capture) mở hộp thoại, hỏi Lưu/Bỏ/Tiếp tục trước
    nếu có chữ chưa lưu. "Choose another image" mở `wp.media` (chỉ ảnh, một ảnh)
    và điền alt của ảnh vừa chọn; Esc trong khung media không đóng hộp thoại của
    mình. Lưu xong `<img>` trên trang đổi ngay (bỏ `srcset`/`sizes` cũ) kèm
    "Saved · Undo". `wp_enqueue_media()` chỉ chạy cho người có `upload_files` và
    chỉ khi site có block ảnh sửa được; khách không bao giờ nhận. Người không có
    `upload_files` không thấy nút chọn ảnh nhưng vẫn sửa được alt.

    **Kiểm:** 1.579 test PHP (10 ca cho `Image_Source`); trên site thật qua
    `handle_save()` với ảnh thật: đổi ảnh Image/Info Box/Timeline (item 1, item 0
    nguyên), chỉ đổi alt, Hoàn tác, từ chối ID không phải ảnh / không tồn tại /
    contributor chọn ảnh (403) / thuộc tính chưa khai báo / thiếu index. Đã thử
    trong trình duyệt (giao diện, `wp.media`, lớp phủ, Before/After).
    **Bổ sung:** Images Gallery (ảnh là chính mục trong `images[]`: khai báo
    `repeat` không có `key`, render gắn `data-flexa-item`; đổi ảnh thì xoá caption
    cũ lưu sẵn, chặn lightbox khi bấm); ảnh nền của Slide (`background: true`:
    chỉ thay khi nền đang là ảnh, giữ position/size/repeat, CSS dựng lại khi
    lưu; mở bằng nút "Change background" ở góc slide vì nền không có `<img>`);
    Before/After mở bằng hai nút "Edit before/after image" ở góc dưới thay vì bấm
    vào ảnh (bấm vào khung là kéo thanh trượt). Nút được xử lý uỷ quyền từ
    `document` (slider sao chép slide sau khi script chạy) và chặn pointerdown để
    không kéo slider/swiper. Click vào ảnh giờ bắt ở block root (capture) để chạy
    trước lightbox của block. Kiểm trên site thật qua `handle_save()`: gallery
    đổi mục 1 giữ mục 0, chỉ đổi alt mục 0, slide đổi nền (CSS mới có URL mới),
    nền màu bị từ chối, before/after đổi ảnh. Đã thử trên trình duyệt (2026-11-19).
    **Chưa làm:** thêm ảnh vào chỗ chưa có ảnh (trang không có `<img>` để bấm);
    nạp `wp.media` muộn khi bấm lần đầu (hiện nạp cùng trang cho người sửa).

## Giai đoạn 7 · Sửa icon

Attribute icon có hình dạng `{ source, name, markup, url, id }` (`source`:
`none` / `builtin` / thư viện / `upload`). Có ở: Icon, Button, Notice, Tab, Info Box
(`icon`, `buttonIcon`), Banner và CTA (`primaryIcon`, `secondaryIcon`), Modal
(`triggerIcon`), Video Popup (`playIcon`), Slides (mũi tên), và trong repeater của
Icon List, Counter, Team Member, Timeline (`items[].icon`).

24. ✅ **Chốt cách server có SVG** (mục mở, xem dưới). Nguyên tắc không đổi: server
    không bao giờ lưu nguyên SVG client gửi lên. Hướng đề xuất: client gửi
    `source` + `name`; server tra SVG từ một registry icon phía PHP rồi mới ghi
    `markup`. Nếu chưa có registry PHP thì tối thiểu chạy
    `Html_Helpers::svg_kses()` trên markup trước khi ghi, và giới hạn kích thước.
25. ✅ Front-end: popover chọn icon dùng lại dữ liệu của `src/components/IconPicker.tsx`,
    nhưng tách phần dữ liệu thư viện ra một module nạp lazy (chỉ khi người dùng
    click vào icon), để trang không phải tải cả thư viện cho mọi lần xem.
26. ✅ Icon dạng `upload` đi đường của ảnh (mục 22): chỉ nhận ID attachment, kiểm
    là SVG/ảnh hợp lệ, server dựng `url`.

    **Đã làm (mục 24–26, đã thử trên trình duyệt 2026-11-20):** chốt cách đầu ở mục mở bên
    dưới. `scripts/build-icon-catalog.cjs` (chạy trong `npm run build`, hoặc
    `npm run icons`) sinh `includes/icons/catalog.php` bằng đúng cách IconPicker
    dựng SVG: 6 icon builtin (đường nét tách ra `src/shared/builtin-icons.json`,
    IconPicker đọc cùng file) và 336 icon của `@wordpress/icons` qua `<Icon>` +
    `normalizeLibrary()`. File sinh ra được commit để checkout chạy được không cần
    build. `Icon_Source` (`includes/class-icon-source.php`) chỉ nhận `{source,
    name}` có trong catalog, hoặc `{source: upload, id}` là attachment ảnh (kể cả
    SVG) mà user có `upload_files` + `read_post`, hoặc `none` (bỏ icon); markup gửi
    kèm bị bỏ qua. Filter `flexa_block_inline_icon_catalog` để add-on thêm icon.
    Registry có khoá `icons` (`selector` là icon, hoặc `wrap` khi là phần tử chỉ
    chứa icon; `repeat` + `key`): Icon, Button, Banner/CTA (nút chính/phụ), Info
    Box (icon và icon nút), Modal (trigger icon), Video Popup (nút play), Icon
    List, Counter, Timeline (marker, kể cả marker chưa có icon). Lưu qua
    `handle_save()` với `icon: true`, giữ các khoá khác (vị trí trước/sau chữ),
    log `kind: icon` dạng `library: wp-arrowRight`; icon cũ không tra lại được
    (ví dụ tên không có trong catalog) thì không Undo được. Route
    `GET flexa-block/v1/inline-icons` trả catalog (đã `svg_kses`) cho người sửa
    được, chỉ tải ở lần bấm icon đầu tiên. Hộp thoại **Edit icon**: icon hiện tại,
    ô tìm, hai tab Basic / WordPress, lưới icon, "Choose file" (thư viện media),
    "Remove icon"; bấm icon (cả trong link/nút) mở hộp thoại thay vì bấm link.
    **Chưa làm:** mũi tên Slides, icon của Tabs, Notice và mạng xã hội của Team
    Member; thêm icon vào chỗ chưa có phần tử icon (trừ marker Timeline).

## Giai đoạn 8 · Tuỳ chọn: lưu thành bản sao chờ duyệt

27. ✅ Cài đặt mới trong tab **Editing**: "Sửa trên trang đã publish thì lưu thành
    bản sao chờ duyệt". Bật thì lần lưu đầu trên trang publish tạo working copy
    (`Working_Copy` của module MCP, cũng cần đưa ra khỏi `includes/mcp/` như mục 1),
    các lần sau ghi vào bản sao đó, toolbar báo "thay đổi đang chờ duyệt" và đưa
    link mở bản sao. Áp dụng vẫn bằng nút "Apply to original" trong block editor.
    Tắt (mặc định) thì giữ hành vi hiện tại: ghi thẳng.


    **Đã làm (đã thử trên trình duyệt 2026-11-20):** công tắc "Send changes to published pages
    for review" (`inline_editor.review`, mặc định tắt) dưới công tắc chính ở tab
    Editing. Khi bật, `handle_save()` ghi thay đổi trên trang đã publish vào bản sao
    của `Working_Copy` (nạp file khi cần, không phụ thuộc module MCP đang bật): bản
    sao đang mở thì là nơi lưu ngay từ đầu; chưa có thì kiểm phiên bản trên trang,
    và chỉ tạo bản sao khi thật sự có thay đổi để ghi (lưu không đổi gì không sinh
    bản sao). Khoá editor của trang gốc không chặn lần tạo bản sao. Template part,
    synced pattern và trang chưa publish vẫn ghi thẳng. Người sửa xem trang sẽ thấy
    nội dung bản sao (`the_content` ưu tiên 1, chỉ cho người được sửa và chỉ trang
    đang xem; `blockId`, phiên bản nội dung tính theo bản sao), khách vẫn thấy trang
    gốc. Toolbar báo "Changes to this page go to a copy for review", lưu xong báo
    "Saved to the copy for review" và hiện link mở bản sao. Duyệt bằng nút "Apply
    to original" có sẵn; `Working_Copy::apply()` phát action
    `flexa_block_working_copy_applied` trước khi xoá bản sao để log sửa ngoài
    front-end chuyển sang trang gốc. Kiểm trên site thật qua `handle_save()`: tắt
    thì ghi thẳng; bật thì trang giữ nguyên, bản sao nháp nhận thay đổi, lần sau
    dùng lại bản sao, phiên bản cũ của trang bị từ chối, Hoàn tác trên bản sao,
    trang nháp vẫn ghi thẳng, apply đưa nội dung lên trang và chuyển log, sửa tiếp
    sau apply tạo bản sao mới. **Chưa làm:** CSS sinh theo block (ví dụ ảnh nền
    slide) của bản sao chưa hiện khi xem trang, vì trang dùng CSS của trang gốc.

    **Bổ sung sau góp ý (ai duyệt, nhớ chỗ đã sửa):** hai tuỳ chọn khi bật duyệt:
    "Who can apply the changes" (`review_approver`: mặc định **Administrators
    only**, hoặc "Anyone who can publish", tức cả Editor) và "Nobody applies their
    own changes" (`review_no_self`, mặc định tắt: ai đã sửa trong bản sao, kể cả
    admin, không tự apply được). Bản sao do front-end tạo có meta
    `_flexa_review_copy`; `Working_Copy::can_apply()` qua filter mới
    `flexa_block_working_copy_can_apply` áp các luật này (bản sao của AI agent không
    bị ảnh hưởng), panel trong editor nhận lý do qua
    `flexa_block_working_copy_panel_data` và hiện thay cho nút Apply. Bản sao không
    còn chép log và cờ review của trang gốc (`SKIP_META`). Để nhớ chỗ đã sửa: panel
    "Changes for a published page" liệt kê mọi thay đổi trong bản sao (cũ nhất trước,
    Trước/Sau tô đậm/gạch ngang, ai, lúc nào); ngoài front-end các block đã sửa có
    viền cam và nhãn "Waiting for review", góc trái dưới có thanh "Changes waiting
    for review on this page: N · Open the copy"; tab Edit log có nút lọc "Waiting
    for review (N)" và nhãn trên từng thẻ, tên trang là trang gốc. Kiểm trên site
    thật: Editor lưu vào bản sao, Editor bị từ chối apply khi chỉ admin duyệt,
    được apply khi chọn "publish", bị chặn khi bật "không tự duyệt"; admin chưa sửa
    thì apply được, đã sửa thì bị chặn; bản sao của agent không đổi luật; danh sách
    chờ duyệt đúng.

    **Tab Review (chỗ riêng để duyệt):** Flexa Block → **Review** (hiện khi bật duyệt
    hoặc còn bản sao chờ), số đếm trên tab và trên menu Flexa Block của WordPress.
    Mỗi trang có bản sao là một thẻ: tên trang, người sửa (ảnh đại diện), lần sửa
    cuối, số thay đổi, danh sách Trước/Sau; nút **Apply to the live page** (đi qua
    đúng route `working-copy/{id}/apply`, nên vẫn kiểm quyền, khoá, xung đột; xung
    đột thì cần tick "ghi đè"), **Discard** (có hộp xác nhận, bản sao vào thùng rác),
    **Open in editor**, **View live page**; người không được duyệt thấy lý do thay
    cho nút Apply. `Inline_Review` (`includes/class-inline-review.php`): route
    `GET flexa-block/v1/inline-review` và `POST …/inline-review/{id}/discard` (chỉ
    `manage_options`). Khi apply (từ tab hay từ nút trong editor) hoặc discard, log
    của bản sao chuyển sang trang gốc và mỗi dòng được đóng dấu quyết định:
    `review` (`approved` / `discarded`), người quyết định (tên, vai trò) và thời
    điểm; tab Edit log hiện dòng "Approved by … · giờ" (xanh) hoặc "Discarded by …"
    (xám). Kiểm trên site thật: hàng đợi liệt kê đúng, apply đóng dấu approved kèm
    tên và giờ, discard đóng dấu discarded và trang giữ nguyên, số đếm giảm, Editor
    không xem được hàng đợi (403).

    **Báo gần thời gian thực:** dùng Heartbeat có sẵn của WordPress (không thêm kết
    nối hay dịch vụ ngoài). `Inline_Review::heartbeat()` trả số bản sao chờ và một
    chữ ký hàng đợi (đổi khi có bản sao mới, sửa thêm, apply, discard); một đoạn
    script nhỏ trên mọi trang admin (chỉ cho `manage_options`, chỉ khi bật duyệt
    hoặc còn bản sao chờ) cập nhật số đỏ trên menu. Tab Review đặt Heartbeat khoảng
    15 giây, khi chữ ký đổi thì hiện "There are new changes waiting for review" và
    nút "Show them", không tự tải lại danh sách. **Vá lỗ hổng Apply:** danh sách gửi
    kèm dấu nội dung bản sao (`Working_Copy::fingerprint()`), nút Apply gửi lại dấu
    đó (`seen`); nếu bản sao đã có sửa mới từ lúc mở danh sách, route apply trả 409
    `flexa_working_copy_changed` và không ghi gì. Kiểm trên site thật: Heartbeat
    tăng số đếm và đổi chữ ký khi có sửa mới; apply với dấu cũ bị từ chối, trang
    giữ nguyên; tải lại rồi apply thì thành công.

## Giai đoạn 9 · Trải nghiệm và tương thích

28. ✅ **Dán chữ.** Khi dán vào trường đang sửa, chặn sự kiện `paste` và chỉ chèn
    phần HTML nằm trong allowlist của trường đó (hoặc chữ thường với trường
    `minimal`), để người dùng không thấy định dạng lạ từ Word / Google Docs trước
    khi lưu. Server vẫn kses như cũ; đây chỉ là để thứ người dùng thấy khớp với
    thứ sẽ được lưu.
    **Đã làm (đã thử trên trình duyệt 2026-11-20):** mỗi trường gửi kèm tên allowlist
    (`allowed`) xuống trình duyệt; sự kiện `paste` trong chữ đang sửa bị chặn và
    thay bằng nội dung đã lọc theo đúng danh sách thẻ server giữ cho trường đó
    (`plain` chỉ chữ; `minimal` đậm/nghiêng/xuống dòng; thêm link với
    `minimal-link`, `rich`; `post` giữ cả đoạn, danh sách). Mọi thuộc tính bị bỏ
    (trừ `href` của link đã qua kiểm scheme), đậm/nghiêng viết bằng style (Word,
    Docs) thành `<strong>` / `<em>` (thẻ `<b style="font-weight:normal">` bọc ngoài
    của Docs không tính), khối không giữ được thành xuống dòng hoặc khoảng trắng,
    comment/ảnh/script/style của Word bị bỏ; trường ghép (`join`) coi như chữ thuần.
    Không đổi server. Kiểm bằng HTML mẫu kiểu Word và Google Docs cho cả năm mức.

29. 🧪 **Cache trang.** Kiểm với Flexa Cache, WP Fastest Cache và một plugin cache
    phổ biến khác: sau khi lưu ngoài front-end, khách có thấy nội dung mới ngay
    không. `wp_update_post()` chạy `save_post` nên phần lớn plugin cache tự xoá;
    chỗ nào không thì gọi hook xoá cache của bài (`clean_post_cache` và action
    riêng của plugin cache nếu có).

    **Kết quả kiểm (Flexa Cache):** sửa trang thì file cache của trang bị xoá đúng
    (Flexa Cache nghe `transition_post_status`); Apply bản sao cũng vậy; sửa vào bản
    sao không cần xoá. **Lỗi:** sửa synced pattern hoặc template part không xoá cache
    của các trang dùng nó (không phải bài công khai, plugin cache không biết trang
    nào nhúng). **Đã sửa:** `Inline_Cache::purge_all()` (`includes/class-inline-cache.php`)
    gọi sau khi lưu vào nơi dùng chung (`targetId`): Flexa Cache (`flexa_cache/purge`),
    WP Fastest Cache, WP Rocket, LiteSpeed, W3 Total Cache, WP Super Cache,
    SiteGround, Cache Enabler, Breeze nếu đang chạy, cùng action
    `flexa_block_inline_purge_all`. Kiểm lại trên site: sửa pattern xoá file cache
    của trang chứa nó. Ghi chú: máy dev chạy nginx với `WP_CACHE` tắt nên Flexa Cache
    ghi file mà không phục vụ; kiểm bằng việc file cache có bị xoá. Chưa thử các
    plugin cache khác (chỉ cài, chưa bật).
30. 🧪 **Điện thoại và bàn phím.** Chạm để sửa trên màn hình nhỏ (toolbar không che
    trường đang sửa, không bị bàn phím ảo che), phím Tab chuyển giữa các trường
    sửa được, nhãn `aria-label` và vai trò cho toolbar, thông báo lưu
    thành công/thất bại đọc được bằng trình đọc màn hình (`aria-live`).

    **Đã làm:** chữ, ảnh, icon sửa được có `tabindex="0"`, `role="button"` và
    `aria-label` ("Edit text: …", "Edit image", "Edit icon"); Enter/Space mở sửa
    (chạy đúng handler click; khi đang gõ thì không chặn phím); lúc đang sửa bỏ
    `role` (là ô chữ), sửa xong gắn lại. Hộp thoại ảnh và icon giữ Tab bên trong
    (`trapTab()`). Toolbar `role="toolbar"` + `aria-label`, thông báo đã có
    `aria-live`. Toolbar xuống dòng (`flex-wrap`); dưới 600px bám đỉnh màn hình
    (thanh admin cuộn đi), thông báo một dòng riêng, nút to hơn. Màn hình cảm ứng
    (`hover: none`) có viền nét đứt mờ cố định ở chỗ sửa được; `:focus-visible` có
    viền rõ. **Hai lỗi tìm ra khi kiểm, đã sửa:** (1) Safari: mở sửa bằng bàn phím
    thì ô có focus nhưng không có con trỏ, gõ không vào; nay `startEdit()` đặt con
    trỏ ở cuối chữ khi chưa có. (2) Đang có chữ chưa lưu mà bấm ảnh/icon nằm trong
    link: bộ chặn rời trang coi là bấm link, chọn "Discard" thì trang đi theo link
    thay vì mở hộp thoại; nay bộ chặn bỏ qua ảnh, icon và nút sửa ảnh. Kiểm tự động
    bằng bản build cuối trên Chromium / WebKit / Firefox: bàn phím + điện thoại
    16/16 / 16/16 / 12/12 (Firefox không giả lập điện thoại), cùng các bộ cũ: chữ
    chưa lưu 14/14, Hoàn tác 9/9, link 19/19, ảnh 23/23 trên cả ba. Chưa thử trên
    điện thoại thật và với trình đọc màn hình thật.
31. ✅ **Lưu block ngoài bài vào đúng nơi** (làm sớm, sau mục 6). Lúc render,
    `enter_scope()` / `leave_scope()` (trên `pre_render_block` ưu tiên cuối và
    `render_block`) giữ một stack nơi lưu của block đang render: `core/post-content`
    (bài), `core/block` (synced pattern, bài `wp_block`), `core/template-part`
    (bài `wp_template_part` nếu đã được tuỳ biến). `target_for()` quyết định:
    block của bài đang xem sửa như cũ; block trong template part cần
    `edit_theme_options`; block trong synced pattern cần `edit_post` trên pattern
    đó; block ở chỗ khác (bài khác trong query loop, viết thẳng trong template,
    template part còn nằm trong file của theme) không có viền sửa. Block sửa được
    ở nơi dùng chung có thêm `data-flexa-edit-target` và `data-flexa-edit-scope`;
    toolbar hiện cảnh báo "lưu sẽ đổi trên mọi trang" ngay khi bắt đầu sửa, và
    gửi `targetId` khi lưu. Server kiểm lại loại bài và quyền
    (`shared_target()`) ở cả `rest_permission()` lẫn `handle_save()`, rồi ghi vào
    đúng bài đó bằng cùng đường lưu của giai đoạn 1. Log vẫn ghi trên trang đang
    xem. Template part chỉ có trong file theme **chưa** sửa được: lưu nó cần tạo
    bản ghi đè trong DB như Site Editor làm.
    Kiểm trên site: lưu vào synced pattern test (2911) qua trang chứa nó (2912):
    pattern đổi, `{}` còn, trang chứa không đổi; footer (template part 1895) với
    tài khoản Editor bị từ chối 403 kèm lý do; `targetId` là trang thường bị từ
    chối. **Chưa kiểm trên trình duyệt**: viền sửa ở footer/pattern và lưu footer
    bằng tài khoản admin.

---

## Đang để mở

- **Sửa video (để bản sau).** Video Popup lưu video bằng `videoSource`
  (`youtube` / `vimeo` / `mp4`) và `videoUrl`; hiện ngoài front-end chỉ đổi được
  ảnh thumbnail. Hướng đã bàn: thêm phần Video vào hộp thoại của block (chọn
  nguồn, dán link YouTube/Vimeo, hoặc chọn file từ thư viện media cho MP4).
  Server kiểm link YouTube/Vimeo bằng cùng mẫu block đang dùng để lấy mã video,
  với MP4 chỉ nhận ID video trong thư viện rồi tự lấy URL (như ảnh); lưu qua
  `handle_save()` nên có phiên bản nội dung, log và Hoàn tác. Còn chốt: có cho
  dán URL MP4 ngoài thư viện không, và có làm cho block khác có video không.

- **Nguồn SVG cho icon thư viện (mục 24) — đã chốt cách đầu, xem mục 24.** Thư viện icon hiện chỉ có ở phía JS
  (`IconPicker.tsx`); markup được client ghi vào attribute lúc chọn trong editor,
  server chỉ lọc bằng `svg_kses()` khi render. Có hai lựa chọn: sinh một file
  registry PHP từ cùng nguồn dữ liệu lúc build (server tự tra theo `name`, an toàn
  nhất), hoặc tin markup client gửi sau `svg_kses()` (nhanh hơn, phụ thuộc vào độ
  chặt của `svg_kses()`). Đề xuất cách đầu.
- **Gộp nhiều trường thành một lần lưu.** Hiện một trường một request. Nếu thêm
  nút "Lưu tất cả" thì nên gửi mảng thay đổi và áp trong một `wp_update_post()`,
  như `flexa/edit-page-blocks` đang làm. Mục 16 giải quyết phần revision trước mà
  không cần đổi cách lưu từng trường.
- **Định dạng chữ trên toolbar** (cỡ, màu, đậm/nghiêng) vẫn chờ chốt hướng như
  ghi chú trong `inline-editing-coverage.md`; không nằm trong file này.

## Rủi ro đã biết

- **Đọc nội dung qua `/wp/v2/pages` không cho thấy nội dung thật.** Core 6.6+
  chạy block hooks trên **câu trả lời** REST của bài
  (`insert_hooked_blocks_into_rest_response`), tức là parse-và-serialize lại
  `content.raw` trước khi trả về, nên `{}` hiện thành `[]` dù trong DB vẫn là
  `{}`. Muốn kiểm nội dung đang lưu thì đọc qua
  `/wp/v2/pages/{id}/revisions?context=edit` (revision mới nhất). Khi cập nhật
  bài qua REST (block editor), core cũng chạy block hooks lên nội dung
  (`update_ignored_hooked_blocks_postmeta`); đường lưu inline không đi qua đó.

- **Giai đoạn 1 đổi đường lưu của 35 block đang chạy.** Phải chạy lại toàn bộ
  coverage trước khi merge, nhất là Comparison Table (ô 2 chiều) và các repeater
  mà attribute chưa có trong JSON (đang dùng default từ `block.json`).
- **`wp.media` trên front-end** kéo theo khá nhiều script (Backbone, media views).
  Chỉ được nạp cho user có quyền sửa, và nên nạp lúc click lần đầu thay vì lúc tải
  trang.
- **Bỏ KSES khi ghi (mục 4)** chỉ đúng khi mọi giá trị đã được lọc theo allowlist
  của trường trước đó. Trường link, ảnh, icon mới phải giữ nguyên tắc này: server
  kiểm hoặc tự dựng giá trị, không ghi nguyên thứ client gửi.
- **Gộp revision (mục 16)** đụng vào cơ chế revision của core. Phải chắc rằng lưu
  từ block editor không bị ảnh hưởng: chỉ áp dụng cho request của inline editor.
- **Lưu vào template part (mục 31)** thay đổi mọi trang dùng phần đó. Quyền và cảnh
  báo phải rõ hơn hẳn so với sửa một trang.
