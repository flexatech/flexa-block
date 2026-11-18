# Sửa ngoài Front-End: nâng cấp

> ✅ xong (7) · 🧪 đang làm (0) · ⬜ chưa bắt đầu (24)
> · Cập nhật: 2026-11-18 · Nhánh: `feat/frontend-editing` (tách từ `feat/mcp-module-phase-0`)
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

7. ⬜ Khi render toolbar, đưa `post_modified_gmt` của bài vào boot data. Mỗi lần
   lưu gửi kèm `expectedModified`; server so với bài hiện tại, lệch thì trả 409
   `flexa_inline_stale`. Lưu thành công trả về `modified_gmt` mới để JS dùng cho
   lần lưu sau (người sửa liên tiếp nhiều trường trên một trang không bị tự chặn).
8. ⬜ Từ chối khi người khác đang giữ khoá bài (`wp_check_post_lock()`), báo tên
   người đó. Không tự lấy khoá: front-end không giữ phiên sửa như editor.
9. ⬜ Gửi kèm giá trị cũ mà JS đang hiển thị (`expect`); server từ chối nếu attribute
   hiện tại khác. Đây là lưới thứ hai cho trường hợp `modified_gmt` khớp nhưng hai
   tab cùng sửa một trường.
10. ⬜ Toolbar xử lý 409: thông báo "trang đã thay đổi, tải lại để sửa tiếp", giữ
    nguyên chữ người dùng vừa gõ để họ copy, không tự ghi đè.

## Giai đoạn 3 · Hỏi trước khi rời đi khi còn thay đổi chưa lưu

"Có thay đổi chưa lưu" nghĩa là trường đang sửa có giá trị khác `originalHTML`
lúc bắt đầu sửa (so sau khi chuẩn hoá khoảng trắng, để click vào rồi click ra
không bị tính là đã sửa), hoặc một lần lưu đang chạy dở.

11. ⬜ Theo dõi trạng thái `dirty` trong `inline-editor.ts`: hàm `isDirty()` so
    `currentValue()` với giá trị gốc. Lưu thành công hoặc bấm Huỷ thì hết dirty.
12. ⬜ **Bấm sang trường khác khi đang dirty**: không bỏ bản đang sửa nữa, mà hiện
    hộp hỏi ngay trên toolbar với ba nút: **Lưu** (lưu rồi chuyển sang trường
    mới), **Bỏ thay đổi** (khôi phục rồi chuyển), **Tiếp tục sửa** (ở lại trường
    cũ). Không dùng `window.confirm()` vì nó chỉ có hai nút và không dịch được
    theo plugin. Lưu thất bại (ví dụ 409 ở giai đoạn 2) thì ở lại trường cũ, giữ
    nguyên chữ.
13. ⬜ **Bấm link trong trang khi đang dirty** (menu, nút, link bất kỳ không phải
    trường đang sửa): bắt sự kiện `click` ở pha capture trên `document`, chặn điều
    hướng, hiện cùng hộp hỏi ở mục 12. Chọn Lưu hoặc Bỏ thay đổi thì mới đi tới
    `href` đã chặn (giữ `target="_blank"` và phím Ctrl/Cmd như trình duyệt). Áp
    dụng cả cho `submit` của form trên trang.
14. ⬜ **Reload, đóng tab, gõ địa chỉ mới, nút Back** khi đang dirty hoặc đang lưu:
    gắn `beforeunload` (`event.preventDefault()` + `returnValue`). Trình duyệt chỉ
    cho hiện **hộp thoại mặc định của nó** ("Rời khỏi trang? Thay đổi có thể không
    được lưu"), không cho đổi chữ và không cho thêm nút Lưu; đây là giới hạn của
    trình duyệt, không làm khác được. Chỉ gắn listener khi dirty và gỡ ngay khi hết
    dirty, để không cản bfcache và không hỏi vô cớ.
15. ⬜ Phím **Escape** khi đang dirty: hỏi "Bỏ thay đổi?" thay vì khôi phục ngay.
    Không dirty thì thoát ngay như hiện tại. Thêm chuỗi dịch mới vào `boot.i18n`
    (`Inline_Editor::enqueue()`), kiểm tra bằng tay trên Chrome, Firefox, Safari:
    bấm trường khác, bấm link menu, reload, đóng tab, Back, Escape.

## Giai đoạn 4 · Lịch sử gọn và hoàn tác

16. ⬜ **Gộp revision theo phiên sửa.** Các lần lưu của cùng một user trên cùng một
    bài trong một khoảng ngắn (ví dụ 10 phút, cho qua filter) chỉ để lại một
    revision: lần lưu đầu tạo revision như bình thường, các lần sau tắt tạo
    revision (`wp_revisions_to_keep` / `wp_save_post_revision_check_for_changes`
    theo request) và cập nhật revision của phiên. Mục tiêu: sửa 20 chỗ liền nhau
    thì lịch sử có một mốc "trước khi sửa ngoài front-end".
17. ⬜ **Ghi log ra khỏi comment.** Thay comment "note" của `log_edit()` bằng một
    nơi lưu riêng (post meta có giới hạn số dòng, hoặc dùng chung cơ chế với
    activity log của module MCP), hiển thị trong một panel ở block editor. Giữ
    filter để site nào vẫn muốn comment thì bật lại. Có bước dọn các comment
    "note" cũ (tuỳ chọn, không tự xoá).
18. ⬜ **Hoàn tác ngay sau khi lưu.** Lưu xong toolbar hiện "Đã lưu · Hoàn tác"
    trong vài giây. Hoàn tác gửi lại giá trị cũ của đúng trường đó (server trả về
    `before` khi lưu), đi qua cùng các kiểm tra của giai đoạn 1–2, nên không hoàn
    tác đè lên thay đổi của người khác.

## Giai đoạn 5 · Sửa link

19. ⬜ Registry: loại trường `'type' => 'url'` gắn với trường chữ đi kèm (nút: `text`
    + `url`; heading: `content` + `url`; info box, CTA, banner: nút chính/phụ; icon
    list: `items[].link.url`). Khi đang sửa chữ của một phần tử có link, toolbar có
    thêm nút "Sửa link" mở popover nhập URL và tuỳ chọn mở tab mới nếu block có
    attribute đó. Server kiểm URL cùng luật với MCP (`http`, `https`, `mailto`,
    `tel`; từ chối `javascript:`), dùng chung hàm kiểm của `Preset_Slots`.

## Giai đoạn 6 · Sửa hình ảnh

Attribute ảnh trong plugin có cùng hình dạng `{ id, url, alt, width, height }`:

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

20. ⬜ Registry: loại trường mới `'type' => 'image'` (cạnh trường chữ hiện có), khai
    báo `attr` (+ `repeat`/`key` cho Timeline) và `selector` của thẻ `<img>`. Filter
    `flexa_block_inline_editable` nhận loại mới để add-on dùng được.
21. ⬜ Front-end: click ảnh mở **media modal của WordPress** (`wp.media`). Chỉ
    `wp_enqueue_media()` khi toolbar thật sự render cho user có quyền, không nạp
    cho khách. Chọn xong đổi `src` tạm để xem trước, rồi lưu.
22. ⬜ Server **chỉ nhận ID attachment**, không nhận URL hay kích thước từ client.
    Kiểm: attachment tồn tại, là ảnh (`wp_attachment_is_image`), user đọc được
    (`read_post`), user có `upload_files`. Rồi tự dựng `url` / `width` / `height`
    từ attachment theo đúng `imageSize` của block, `alt` lấy alt của attachment
    nếu block đang trống. Dùng lại cách `Preset_Slots::clean_media()` đang kiểm.
23. ⬜ Sửa alt text tại chỗ (ô nhập nhỏ trên ảnh) cho `image.alt`: là chữ, đi
    đường lưu chữ hiện có.

## Giai đoạn 7 · Sửa icon

Attribute icon có hình dạng `{ source, name, markup, url, id }` (`source`:
`none` / `builtin` / thư viện / `upload`). Có ở: Icon, Button, Notice, Tab, Info Box
(`icon`, `buttonIcon`), Banner và CTA (`primaryIcon`, `secondaryIcon`), Modal
(`triggerIcon`), Video Popup (`playIcon`), Slides (mũi tên), và trong repeater của
Icon List, Counter, Team Member, Timeline (`items[].icon`).

24. ⬜ **Chốt cách server có SVG** (mục mở, xem dưới). Nguyên tắc không đổi: server
    không bao giờ lưu nguyên SVG client gửi lên. Hướng đề xuất: client gửi
    `source` + `name`; server tra SVG từ một registry icon phía PHP rồi mới ghi
    `markup`. Nếu chưa có registry PHP thì tối thiểu chạy
    `Html_Helpers::svg_kses()` trên markup trước khi ghi, và giới hạn kích thước.
25. ⬜ Front-end: popover chọn icon dùng lại dữ liệu của `src/components/IconPicker.tsx`,
    nhưng tách phần dữ liệu thư viện ra một module nạp lazy (chỉ khi người dùng
    click vào icon), để trang không phải tải cả thư viện cho mọi lần xem.
26. ⬜ Icon dạng `upload` đi đường của ảnh (mục 22): chỉ nhận ID attachment, kiểm
    là SVG/ảnh hợp lệ, server dựng `url`.

## Giai đoạn 8 · Tuỳ chọn: lưu thành bản sao chờ duyệt

27. ⬜ Cài đặt mới trong tab **Editing**: "Sửa trên trang đã publish thì lưu thành
    bản sao chờ duyệt". Bật thì lần lưu đầu trên trang publish tạo working copy
    (`Working_Copy` của module MCP, cũng cần đưa ra khỏi `includes/mcp/` như mục 1),
    các lần sau ghi vào bản sao đó, toolbar báo "thay đổi đang chờ duyệt" và đưa
    link mở bản sao. Áp dụng vẫn bằng nút "Apply to original" trong block editor.
    Tắt (mặc định) thì giữ hành vi hiện tại: ghi thẳng.

## Giai đoạn 9 · Trải nghiệm và tương thích

28. ⬜ **Dán chữ.** Khi dán vào trường đang sửa, chặn sự kiện `paste` và chỉ chèn
    phần HTML nằm trong allowlist của trường đó (hoặc chữ thường với trường
    `minimal`), để người dùng không thấy định dạng lạ từ Word / Google Docs trước
    khi lưu. Server vẫn kses như cũ; đây chỉ là để thứ người dùng thấy khớp với
    thứ sẽ được lưu.
29. ⬜ **Cache trang.** Kiểm với Flexa Cache, WP Fastest Cache và một plugin cache
    phổ biến khác: sau khi lưu ngoài front-end, khách có thấy nội dung mới ngay
    không. `wp_update_post()` chạy `save_post` nên phần lớn plugin cache tự xoá;
    chỗ nào không thì gọi hook xoá cache của bài (`clean_post_cache` và action
    riêng của plugin cache nếu có).
30. ⬜ **Điện thoại và bàn phím.** Chạm để sửa trên màn hình nhỏ (toolbar không che
    trường đang sửa, không bị bàn phím ảo che), phím Tab chuyển giữa các trường
    sửa được, nhãn `aria-label` và vai trò cho toolbar, thông báo lưu
    thành công/thất bại đọc được bằng trình đọc màn hình (`aria-live`).
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

- **Nguồn SVG cho icon thư viện (mục 24).** Thư viện icon hiện chỉ có ở phía JS
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
