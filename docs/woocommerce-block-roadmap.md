# Block WooCommerce

> ✅ đã xong (9: 5 phát hành + 4 gộp) · 🧪 vừa build, đang kiểm tra (6) · ⬜ còn thiếu (38)
> · Cập nhật: 2026-09-17
>
> 🧪 = code + test đã xanh (`npm run build`, `npm run typecheck`, `phpunit` 1448 test,
> `npm run lint:reuse`, `.pot`, `wpml-config.xml`), còn chờ kiểm thử thật trên trang sản phẩm
> WooCommerce.
>
> **Đọc [§ Hợp nhất block](#-hợp-nhất-block) trước khi build tiếp từ #20.** Danh sách 53 mục dưới đây
> có nhiều chỗ chồng chức năng nhau; 4 chỗ đã gộp xong, phần còn lại có kế hoạch sẵn và sẽ tiết kiệm
> nhiều nhất ở cụm vòng lặp sản phẩm (#19–#33).

---

## Trang sản phẩm (single product)

1. ✅ Product Name — `src/blocks/product-name/`
2. ✅ Product Price — `src/blocks/product-price/`
3. ✅ Product Image — `src/blocks/product-image/` · *2026-09-14: đổi ảnh theo variation · 2026-09-17: lightbox toàn màn hình*
4. ✅ Product Rating — `src/blocks/product-rating/`
5. ✅ Product Details — `src/blocks/product-detail/`
6. 🧪 Add to Cart — `src/blocks/product-add-to-cart/`
7. 🧪 Product Excerpt — `src/blocks/product-excerpt/`
8. 🧪 Product Description — `src/blocks/product-description/`
9. 🧪 Product Stock — `src/blocks/product-stock/`
10. ✅ Product SKU — **đã gộp**: variation của `flexa/product-field`
11. 🧪 Product Meta — `src/blocks/product-meta/` — **container** chứa các `flexa/product-field`
12. ✅ Product Categories — **đã gộp**: variation của `flexa/product-field`
13. ✅ Product Tags — **đã gộp**: variation của `flexa/product-field`
14. ⬜ Additional Information — `src/blocks/product-additional-info/`
15. ⬜ Product Reviews — `src/blocks/product-reviews/`
16. ✅ Product Share — **đã gộp**: variation `product-share` của `flexa/social-share`
17. ⬜ Call for Price — `src/blocks/product-call-for-price/`
18. ⬜ Product QR Code — `src/blocks/product-qr-code/`
19. 🧪 Related Products — `src/blocks/product-related/`
20. ⬜ Up-sells — `src/blocks/product-up-sells/`

> `flexa/product-field` (`src/blocks/product-field/`) là block hiện thực cho #10 / #12 / #13 — một
> block, ba variation, dùng riêng hoặc xếp trong `flexa/product-meta`.

## Trang shop / danh mục (archive)

21. ⬜ Product Grid — `src/blocks/product-grid/`
22. ⬜ Archive Products — `src/blocks/archive-products/`
23. ⬜ Product Categories List — `src/blocks/product-category-list/`
24. ⬜ Archive Title — `src/blocks/archive-title/`
25. ⬜ Archive Description — `src/blocks/archive-description/`
26. ⬜ Result Count — `src/blocks/archive-result-count/`
27. ⬜ Sort By — `src/blocks/product-sort/`
28. ⬜ Products Per Page — `src/blocks/product-per-page/`
29. ⬜ View Mode — `src/blocks/product-view-mode/`
30. ⬜ Product Filter — `src/blocks/product-filter/`
31. ⬜ Product Search — `src/blocks/product-search/`
32. ⬜ Recently Viewed — `src/blocks/product-recently-viewed/`
33. ⬜ Deal Products — `src/blocks/product-deals/`

## Trang giỏ hàng (cart)

34. ⬜ Cart Table — `src/blocks/cart-table/`
35. ⬜ Cart Totals — `src/blocks/cart-totals/`
36. ⬜ Cart Coupon Form — `src/blocks/cart-coupon/`
37. ⬜ Empty Cart Message — `src/blocks/cart-empty-message/`
38. ⬜ Return to Shop — `src/blocks/cart-return-to-shop/`
39. ⬜ Cross-sells — `src/blocks/product-cross-sells/`

## Trang thanh toán (checkout)

40. ⬜ Billing Form — `src/blocks/checkout-billing/`
41. ⬜ Shipping Form — `src/blocks/checkout-shipping/`
42. ⬜ Additional Fields — `src/blocks/checkout-additional/`
43. ⬜ Login Form — `src/blocks/checkout-login/`
44. ⬜ Coupon Form — `src/blocks/checkout-coupon/`
45. ⬜ Shipping Methods — `src/blocks/checkout-shipping-methods/`
46. ⬜ Payment Methods — `src/blocks/checkout-payment/`
47. ⬜ Order Review — `src/blocks/checkout-review-order/`

## Trang tài khoản (my account)

48. ⬜ Account Dashboard — `src/blocks/account-dashboard/`
49. ⬜ Orders — `src/blocks/account-orders/`
50. ⬜ Login / Register — `src/blocks/account-login/`

## Dùng chung (header / mọi trang)

51. ⬜ Mini Cart — `src/blocks/mini-cart/`
52. ⬜ WooCommerce Notices — `src/blocks/woo-notices/`
53. ⬜ WooCommerce Breadcrumb — `src/blocks/woo-breadcrumb/`

---

# 🧩 Hợp nhất block

## Vấn đề

Danh sách 53 mục ở trên liệt kê nhiều block chồng chức năng nhau. Đo trên code thật:

| Chỗ trùng | Mức trùng |
|---|---|
| `product-categories` vs `product-tags` | `render.php` **giống hệt** trừ tên taxonomy; 30/30 attribute trùng tên; 2 CSS generator 36 dòng chỉ để ủy quyền cho một lớp chung |
| `product-meta` vs 3 block trên | Meta có `showSku` / `showCategories` / `showTags` → **bao trọn** cả ba |
| `product-share` vs `social-share` | 582 dòng nhân bản, trong khi `social-share` **đã có** `shareSource` — chỉ thiếu giá trị `product` |
| `product-excerpt` vs `product-description` | 19/32 attribute trùng; Excerpt = Description + `wordLimit` |
| #19 vs #20, #21, #22, #32, #33, #39 | Cùng **một thẻ sản phẩm**; 50 attribute của `product-related` dùng lại nguyên xi, chỉ khác câu query |

Nếu build tiếp y theo danh sách, ta sẽ viết lại cùng một khối UI **7 lần** cho vòng lặp sản phẩm.

## Con số quyết định

| Bundle editor | KB |
|---|---|
| `product-meta` (khi còn gộp 4 việc trong một block) | 82,9 |
| `product-stock` (1 việc) | 74,7 |
| `product-excerpt` (1 việc, 19 attribute) | 71,9 |

Mỗi block tốn khoảng **70 KB cố định** là code khung dùng chung (`@components`, panel, control) lặp
trong từng bundle; phần code riêng chỉ ~8–12 KB. Nên phép tính là: bỏ 1 block ≈ **−72 KB** trong
`build/` + **−0,24 ms** parse `block.json` mỗi request (theo
[toi-uu-hieu-nang-nhieu-block.md](toi-uu-hieu-nang-nhieu-block.md)), đổi lại block lõi phình vài KB.

## Ranh giới — khi nào **không** gộp

Gộp đúng khi các block **dùng chung cả bề mặt style lẫn hình dạng markup**. SKU / Categories / Tags
đạt cả hai: đều là "một nhãn + một giá trị".

Gộp sai khi chúng chỉ chung *chủ đề*. Cụ thể: **đừng** nhét `product-stock` vào Product Meta — nhìn
qua cũng là "nhãn + giá trị", nhưng nó có 4 trạng thái với 8 cặp màu riêng, ngưỡng low-stock và
badge; nhét vào sẽ biến block lõi thành cái sọt. Tương tự `product-add-to-cart` (form Woo, logic
theo loại sản phẩm) và `product-filter` / `product-search` (can thiệp query, không phải bài toán
trình bày).

## Còn lại phải làm

| # | Block lõi | Gom các mục | Thuộc tính phân biệt | Giảm |
|---|---|---|---|---|
| 1 | ✅ `flexa/product-meta` + `flexa/product-field` | 11, 10, 12, 13 *(14: chưa làm)* | con: `field` (`sku` \| `categories` \| `tags`) | **−2** |
| 2 | ✅ `flexa/social-share` | 16 | `shareSource: 'product'` + `includeImage` | **−1** |
| 3 | `flexa/product-content` | 8, 7, 25 | `source` (`long` \| `short` \| `auto` \| `archive`) + `wordLimit` | **−2** |
| 4 | `flexa/product-loop` | 19, 20, 39, 21, 22, 32, 33 | `source` (`related` \| `upsells` \| `crosssells` \| `query` \| `recently-viewed` \| `on-sale` \| `featured` \| `best-selling` \| `newest`) | **−6** |
| 5 | `flexa/shop-toolbar` | 26, 27, 28, 29 | `control` (`result-count` \| `orderby` \| `per-page` \| `view-mode`) | **−3** |
| 6 | `flexa/checkout-fields` | 40, 41, 42 | `fieldset` (`billing` \| `shipping` \| `additional`) | **−2** |
| 7 | `flexa/coupon-form` | 36, 44 | `context` (`cart` \| `checkout`) | **−1** |
| 8 | `flexa/customer-login` | 43, 50 | `mode` (`login` \| `login-register`) | **−1** |
| 9 | `flexa/cart-empty` | 37, 38 | `showButton` (Woo vốn in hai thứ này cạnh nhau) | **−1** |
| 10 | `flexa/product-loop` *(tier 2)* | 23 | `source: 'categories'` — vỏ thẻ giống hệt | **−1** |

**Mục 4 đáng giá nhất** — nó không phải refactor mà là **không viết** 6 block chưa tồn tại. Làm nó
trước khi chạm vào #20.

> 📌 **Bộ template thật đang chặn ở đúng mục 4.** Đối chiếu 7 template `flexa_template` trên
> wptemplate.local cho thấy cả 3 template thương mại điện tử đều phải mượn `product-collection` +
> `product-template` của WooCommerce, vì Flexa không có vòng lặp sản phẩm nào. Chi tiết, kèm cấu
> hình collection thật sự được dùng (`featured`, `on-sale`, lọc tồn kho) và hai block còn thiếu khác
> (sale badge, ảnh thẻ): [doi-chieu-block-woo-trong-template.md](doi-chieu-block-woo-trong-template.md).

## Hai khuôn mẫu, chọn theo tình huống

**A. Một block + `variations`** — khi các mục chỉ khác nhau ở *nguồn dữ liệu*, còn markup và bề mặt
style y hệt. Tiền lệ: [`src/blocks/container/variations.ts`](../src/blocks/container/variations.ts).

```ts
{
  name: 'product-tags',
  title: __( 'Product Tags', 'flexa-block' ),
  keywords: [ __( 'tags', 'flexa-block' ), __( 'taxonomy', 'flexa-block' ) ],
  icon: BLOCK_ICONS[ 'product-tags' ],   // giữ icon cũ → inserter nhìn y như trước
  scope: [ 'inserter', 'transform' ],
  attributes: { field: 'tags', label: 'Tags:' },
  isActive: ( attrs ) => 'tags' === attrs.field,
}
```

- `isActive` **bắt buộc** — không có nó, breadcrumb và inspector hiện tên block lõi thay vì tên
  variation, người dùng tưởng mất block.
- `keywords` cũng gần như bắt buộc — người ta tìm "product code", không tìm "SKU".
- Một `panels.tsx`, ẩn/hiện control theo thuộc tính phân biệt. **Panel không áp dụng thì đừng
  render** — nhờ vậy variation "Product SKU" có inspector gọn đúng bằng block SKU cũ, dù block lõi
  mang 38 attribute.

**B. Container + block con** — khi người dùng cần **đổi thứ tự** và **style riêng từng phần tử**.
Tiền lệ: `subscribe-form` (cha 35 attribute emit `.flexa-subscribe-form-<id> .flexa-field__label`,
13 block con không có CSS generator nào).

Khác `subscribe-form` một điểm quan trọng: block con ở đây **không khóa `parent`**, vì đặt SKU ngay
dưới giá là use case chính. Con dùng được độc lập ⇒ con phải có CSS generator riêng ⇒ sinh ra
**bài toán thứ tự**:

> Cha emit `.flexa-product-meta-<cha> .flexa-product-field__label`, con emit
> `.flexa-product-field-<con> .flexa-product-field__label`. Hai selector **cùng độ đặc hiệu (0,2,0)**
> nên thứ tự ghi quyết định. `CSS_Generator_Service::process_blocks()` sinh CSS của một block **trước**
> khi đệ quy vào inner blocks → con luôn sau cha → **cha đặt mặc định chung, con ghi đè**. Đây là do
> cấu trúc chứ không phải may, và
> `ProductFieldCssTest::test_a_field_rule_lands_after_its_parent_list_rule()` giữ nó không tuột.

## Rủi ro di trú

Các block 🧪 chưa nằm trong bản phát hành nào (1.0.8 không có chúng), nên gộp **không cần
deprecation, không cần `migrate`, không cần bridge attribute** như đợt `itemStyleMigrated`. Cửa sổ
này đóng lại khi tag 1.0.9.

Nhưng **nội dung site demo thì có thật**. Trước khi xóa thư mục block, kiểm tra và sửa **bài LIVE**
(không re-seed):

```sql
SELECT ID, post_type, post_title FROM wp_posts WHERE post_content LIKE '%flexa/<slug>%';
```

5 block ✅ đã phát hành (`product-name`, `product-price`, `product-image`, `product-rating`,
`product-detail`) **không đụng tới**; nếu buộc phải sửa thì chỉ được thêm, không đổi markup / class /
attribute.

## Checklist nghiệm thu sau mỗi bước gộp

- [ ] Inserter vẫn hiện đủ số mục, đúng tên + icon như trước khi gộp.
- [ ] Chọn mỗi variation → inspector hiện đúng tên variation (kiểm `isActive`).
- [ ] Tìm kiếm trong inserter ra đúng mục (kiểm `keywords`).
- [ ] Transform qua lại giữa các variation không mất style đã đặt.
- [ ] CSS `.flexa-<slug>-<id>` in đúng cho từng variation, không rò style của variation khác.
- [ ] `npm run build && npm run typecheck`, `php vendor/bin/phpunit`, `npm run lint:reuse`.
- [ ] Sinh lại `.pot` (php + `wp-cli.phar`, **không** loại trừ `build/`) + `wpml-config.xml`.
- [ ] `BASE_BLOCKS` không còn slug đã xóa; tắt WooCommerce → block ẩn khỏi inserter.
- [ ] Nội dung site demo đã di trú, không còn block mồ côi.

---

# 📓 Nhật ký thực hiện

## 2026-09-14 · Gộp Product Share vào Social Share ✅

**Xóa:** `src/blocks/product-share/` (7 file), `class-product-share-css.php`,
`tests/php/ProductShareCssTest.php`, `src/components/social-panels.tsx` (file "dùng chung" chỉ còn
một consumer), entry `BASE_BLOCKS`, `require_once` trong `tests/test-init.php`, type
`ProductShareAttributes` / `ProductShareItem`.

**Thêm vào `flexa/social-share`** (block **đã phát hành** — mọi thứ thêm vào đều trơ mặc định, nội
dung cũ không đổi một pixel):

| Thêm | Vì sao |
|---|---|
| `shareSource: 'product'` | Nguồn thứ ba bên cạnh `current` / `custom` |
| `includeImage` | Chỉ hiện khi nguồn là product (Pinterest dùng ảnh) |
| `showLabels`, `labelTypography` | Tên mạng cạnh icon — trước chỉ Product Share có |
| `advancedLayout`, ảnh nền + `lazyLoad` | Ngang bằng các block product-* |
| `wooActive` trong `window.flexaBlockEditor` | Block này luôn được đăng ký (khác product-*), nên tùy chọn product phải tự ẩn khi không có Woo |

**Hai quyết định:**

1. **Giữ markup của social-share** (`<div class="__list">` + `<a class="__item">`), không lấy
   `<ul>/<li>` của product-share — đổi markup của block đã phát hành là vỡ CSS người đang dùng.
2. **Nguồn product tự rơi về trang hiện tại** khi không có sản phẩm trong ngữ cảnh — dễ hiểu hơn là
   block biến mất không lý do.

`Social_Share_CSS` viết lại bằng helper dùng chung: **155 → 82 dòng**.

**Kiểm chứng:** phpunit 1491 test xanh, typecheck / build / lint:reuse sạch, `.pot` sinh lại.
DB demo: 0 bài dùng `flexa/product-share` → xóa an toàn.

## 2026-09-14 · Gộp cụm Meta, rồi đổi sang mô hình container ✅

Làm hai nhịp. Nhịp đầu gom SKU / Categories / Tags vào `product-meta` bằng một mảng `fields[]`; nhịp
sau **bỏ `fields[]`** và tách thành container + block con. Ghi lại cả hai vì nhịp sau sửa một đánh
giá sai của nhịp đầu.

**Xóa:** `src/blocks/product-sku/`, `product-categories/`, `product-tags/`, bốn CSS generator (kể cả
`class-product-term-list-css.php` — lớp chung chỉ tồn tại vì hai block taxonomy),
`src/styles/_term-list.scss`, `src/components/woo-panels.tsx`, ba test file, ba entry `BASE_BLOCKS`,
ba entry `wpml-config.xml`, năm type.

**Kết quả cuối:**

```
flexa/product-meta   ← container, InnerBlocks, 21 attribute
└─ flexa/product-field  ← 1 block con, 38 attribute, 3 variation
```

**Vì sao bỏ `fields[]`.** Nó đúng về mặt code nhưng thua về mặt người dùng ở hai điểm không bù đắp
được: đổi thứ tự dòng phải bấm mũi tên trong inspector thay vì kéo thả trong canvas, và mọi dòng
buộc dùng chung một bộ typography. Với InnerBlocks, dòng **là** block — kéo thả, nhân bản,
copy/paste, list view đều là thao tác Gutenberg chuẩn, và mỗi dòng style riêng được.

Lý do trước đó tôi bác InnerBlocks ("cha không điều khiển được cột nhãn của con") là **sai** —
`subscribe-form` trong chính repo này đã làm rồi, bằng descendant selector.

**Hai trục layout, không gộp làm một** (giữ lại từ nhịp đầu): `metaLayout` xếp *các dòng*,
`termLayout` xếp *các term bên trong một dòng* — hai thứ vuông góc. Ép chung vào một enum thì block
vừa có SKU vừa có danh mục không diễn đạt được "dòng kiểu bảng + term kiểu badge". Nhịp sau
`metaLayout` biến mất luôn: "bảng" chỉ còn là "có đặt cột nhãn".

**Một tính năng suýt mất:** `skuLayout: 'stacked'` (nhãn nằm trên giá trị). Phát hiện khi soi template
thật trên site demo; giờ là `fieldLayout` của block con.

**Chia việc:**

| | Cha (`product-meta`) | Con (`product-field`) |
|---|---|---|
| Layout | row gap, label gap, **cột nhãn**, divider | nhãn inline / stacked, label gap |
| Nội dung | — | `field`, nhãn, prefix/suffix/fallback/copy, term layout / separator / link / maxTerms |
| Style | typography + màu nhãn & giá trị **cho mọi dòng** | ghi đè riêng từng dòng + chip term |

**Màu term:** mặc định theo `valueColor` (để danh mục có link trông giống SKU bên cạnh), `itemColor`
ghi đè khi được đặt — generator emit `valueColor` trước, `itemColor` sau, `CSS_Builder` lưu property
theo khóa nên lần ghi sau thắng.

**Di trú nội dung demo (hai lần).** Template `Single Product` (post 2403) + 6 revision. Lần một:
`flexa/product-sku` → `flexa/product-meta` (ánh xạ `prefix→skuPrefix`, `suffix→skuSuffix`,
`fallbackText→skuFallback`, `gap→labelGap`, `typography→valueTypography`,
`showLabel`+`labelText`→`label`, `skuLayout`→`metaLayout`). Lần hai: block một dòng →
`product-field` đứng riêng (giữ nguyên style + `blockId`), block ba dòng → container bọc ba
`product-field`. Còn **0** block phẳng sót lại. Bản sao nội dung cũ nằm trong scratchpad của phiên.
Sửa **bài LIVE**, không re-seed.

**Kiểm chứng:** phpunit 1429 test / 4028 assertion xanh (ProductFieldCssTest 22 test mới,
ProductMetaCssTest viết lại cho vai trò container), typecheck / build / lint:reuse sạch,
`.pot` + `wpml-config.xml` sinh lại.

## 2026-09-14 · Sửa lỗi đợt 3 ✅

**Chung — `colour` → `color`** trong mọi chuỗi người dùng thấy: 34 file TS/TSX (chuỗi trong `__()`),
33 `block.json`, mô tả trong `Block_Manager`. Comment trong code giữ nguyên.

**Add to Cart** — 5 lỗi:

| Lỗi | Nguyên nhân | Cách sửa |
|---|---|---|
| Editor hiện 2 icon, front-end 1 | Editor vừa vẽ `<svg>` vừa gắn class tạo `::before` | Bỏ phần tử SVG; size/gap của pseudo-element mirror qua `<style>` scoped |
| Layout `stacked` sai | Rule `flex-direction:column` nằm trong `@media (min-width:1025px)` | Chuyển vào `style.scss` theo modifier `--stacked` |
| Alignment không tác dụng | Phát `text-align` lên wrapper, mà `form.cart` là flex | Phát `justify-content` lên form, thêm `align-items` khi stacked. Thêm helper dùng chung `CSS_Helpers::flex_align()` (map này bị chép ở 4 chỗ) |
| Nút tăng/giảm xấu | Woo chỉ in `input[type=number]` trần | `view.js` bọc cặp −/+, tôn trọng min/max/step, tự disable ở hai đầu, ẩn spinner mặc định. Viền/bo góc chuyển sang nhóm — **input chỉ bỏ viền khi ở trong nhóm**, nên không JS vẫn còn viền |
| Sản phẩm có option dùng `alert()` | Handler của Woo delegated trên `document` | `view.js` chặn click ở **capture phase**, hiện thông báo inline (`role="status"`, tự ẩn sau 6s). Không tự bật nút — quyền vẫn của Woo |

**Product Excerpt** — màu link không ăn vì `wp_trim_words()` gọi `wp_strip_all_tags()` trước, nên khi
có word limit thì thẻ `<a>` bị xóa sạch. Viết `Woo_Helpers::trim_words_html()`: đếm từ trong text
node, giữ nguyên thẻ, đóng lại thẻ còn mở tại điểm cắt, bỏ qua void element, không thêm "…" khi vừa
đúng số từ. **13 test** riêng. Câu mẫu trong editor cũng cắt theo word limit.

**Product Description** — bỏ hẳn `source`; block luôn in long description, short description để
Product Excerpt lo.

**Product Stock** — editor luôn preview in-stock nên đổi chữ/màu của trạng thái khác không thấy gì.
Thêm **Preview state** trên block toolbar (4 trạng thái, đổi cả icon / chữ / màu chữ / màu nền). Đây
là state của component, **không phải attribute** — không lưu vào nội dung.

**Product Image** — thêm đổi ảnh theo variation. Block dùng carousel riêng nên script của Woo không
chạm tới, và form variation nằm ở **block Add to Cart** — block anh em trên trang. Bind qua jQuery
vào `found_variation` / `reset_data` (custom event của jQuery, `addEventListener` không nhận được);
không có jQuery thì gallery chạy y như cũ. Xử lý thêm: thumbnail tương ứng sáng lên khi ảnh variation
có trong gallery, autoplay dừng khi đã chọn variation, `srcset`/`sizes` xóa hẳn khi variation không
có, và gallery **một ảnh** cũng đổi theo (trước đây hàm init `return` sớm khi không có thumbnail).

**Kiểm chứng:** phpunit **1448 test / 4065 assertion** xanh, typecheck / build / lint:reuse sạch,
jest 12/12, `.pot` sinh lại.

## 2026-09-17 · Lightbox cho Product Image + tách module dùng chung ✅

**Phát hiện trước khi làm:** plugin đã có **hai** bản lightbox gần như giống hệt — `image` và
`images-gallery` — trùng cả TS lẫn SCSS, kể cả hàm `isDarkMode()` chép y nguyên. Thêm bản thứ ba
cho product-image là sai hướng, nên tách trước:

- [`src/shared/lightbox.ts`](../src/shared/lightbox.ts) — overlay, prev/next, phím, vuốt, đóng.
- [`src/styles/_lightbox.scss`](../src/styles/_lightbox.scss) — mixin nhận tiền tố class.

**Tiền tố class là tham số**, nên `image` giữ `.flexa-image-lightbox`, `images-gallery` giữ
`.flexa-images-gallery-lightbox` — hai block đã phát hành không đổi một ký tự nào trong markup hay CSS.
`view.ts` của hai block: **361 → 173 dòng**. Chênh lệch duy nhất: block `image` giờ ship thêm rule
`__nav` (~300 byte CSS chết, nó không bao giờ render nút này) — đổi lại chỉ còn một bản triển khai.

**Product Image** thêm attribute `enableLightbox`, **mặc định tắt** — đây là block đã phát hành trong
1.0.8, bật sẵn sẽ đổi hành vi của site đang chạy. Khi bật:

- `render.php` in thêm `data-large` (bản **full size**) cho ảnh đại diện và từng thumbnail — overlay mở
  ảnh gốc chứ không phóng to bản `woocommerce_single` đang hiển thị.
- Slide là toàn bộ gallery, mở đúng thumbnail đang active.
- **Giao với variation:** ảnh variation nằm ngoài gallery thì không có thumbnail nào active — lúc đó
  ảnh đang xem được chèn thành slide đầu, nếu không click vào sẽ mở ra một tấm khác.
- Tắt caption: gallery sản phẩm lặp tên sản phẩm trên mọi slide, đọc như nhiễu.

**Kiểm chứng:** phpunit 1448 test / 4065 assertion xanh, typecheck / build / lint:reuse sạch,
jest 12/12, `.pot` sinh lại. CSS build ra của cả ba block giữ đúng tên class cũ.

---

# 🧪 Checklist kiểm thử

Chạy trên một trang sản phẩm WooCommerce thật (block chỉ render khi có `$product` trong ngữ cảnh).

## Chung cho tất cả

- [ ] Chèn vào trang single-product → hiển thị ngay với mặc định, không cần cấu hình.
- [ ] Chèn vào trang KHÔNG phải single-product → không in ra gì (không lỗi PHP).
- [ ] Tắt WooCommerce → block ẩn khỏi inserter, trang cũ không vỡ.
- [ ] Trang chỉ có block mới (không Container) → CSS `.flexa-<slug>-<id>` vẫn in trong `<head>`.
- [ ] Dark mode: đổi màu ở tab dark → front-end đổi theo.
- [ ] Responsive: giá trị tablet/mobile vào đúng media query.
- [ ] Preview trong editor khớp front-end (typography, màu, padding).
- [ ] Mọi nhãn trong inspector dùng "color", không còn "colour".

## Product Meta (container) + Product Field

- [ ] Inserter hiện đủ 4 mục: Product Meta / SKU / Categories / Tags, icon cũ giữ nguyên.
- [ ] Gõ "code" / "product code" / "taxonomy" / "copy" trong inserter → ra đúng mục (`keywords`).
- [ ] Chèn **Product Meta** → tự có sẵn 3 dòng SKU / Categories / Tags.
- [ ] Kéo thả đổi thứ tự dòng trong canvas và list view → front-end đổi theo.
- [ ] Xóa hết dòng bên trong → container không in gì (không còn khung rỗng có viền).
- [ ] Chỉ chèn được `product-field` vào trong container (`allowedBlocks`).
- [ ] Chèn **Product SKU** thẳng ra ngoài container (ví dụ dưới giá) → vẫn chạy bình thường.
- [ ] Đổi select **Field** trên một dòng → nhãn tự theo (trừ khi đã gõ chữ riêng), tên variation đổi theo.
- [ ] Panel **Terms** / **Term box** ẩn trên dòng SKU; panel **SKU** ẩn trên dòng taxonomy.
- [ ] **Thử thứ tự cha/con:** đặt màu nhãn ở **Product Meta → Labels** → mọi dòng đổi. Sau đó đặt màu
      nhãn khác trên **một dòng** → chỉ dòng đó đổi. Kiểm cả editor lẫn front-end.
- [ ] **Cột nhãn:** đặt `labelWidth` trên cha → giá trị của mọi dòng thẳng hàng như bảng.
- [ ] Nhãn inline ↔ stacked trên từng dòng.
- [ ] 3 term layout: inline (có separator), badge, list.
- [ ] Giới hạn số term (`maxTerms`) cắt đúng; link tới archive + mở tab mới.
- [ ] Hover term đổi màu chữ + nền (cả editor lẫn front-end).
- [ ] Màu term mặc định theo **Value color**; đặt **Term box → Text color** trên dòng thì đè lên.
- [ ] Sản phẩm thiếu SKU / không có danh mục / không có thẻ → dòng đó tự ẩn; hết dòng → không in gì.
- [ ] Fallback SKU: có chữ → hiện; để trống → ẩn dòng. Prefix / suffix chỉ bọc SKU thật.
- [ ] Nút copy: HTTPS dùng clipboard API, HTTP fallback vẫn copy được; không hiện trên fallback text.
- [ ] Bật đường kẻ → chỉ kẻ giữa các dòng, không kẻ trên dòng đầu.
- [ ] **Hồi quy:** template "Single Product" trên site demo (đã chuyển đổi tự động) hiển thị y như trước.

## Add to Cart

- [ ] Sản phẩm **simple** / **variable** / **grouped** / **external**: form Woo render đủ và mua được thật.
- [ ] Variable: chọn thuộc tính → giá + nút cập nhật bình thường (không bị CSS chặn).
- [ ] Đổi chữ nút (`buttonText`) chỉ ảnh hưởng block này, không rò sang trang khác.
- [ ] `cartLayout = button-only` và tắt số lượng → ô quantity ẩn, vẫn thêm vào giỏ được.
- [ ] Sản phẩm hết hàng → Woo tự đổi thành nút "Read more", block không vỡ.
- [ ] Bật icon giỏ hàng → editor và front-end đều chỉ **một** icon, ăn theo màu chữ nút.
- [ ] Layout `stacked` → qty nằm trên, nút nằm dưới **ở mọi bề rộng màn hình**.
- [ ] Alignment left / center / right → form dịch thật; khi stacked thì dịch cả theo trục dọc.
- [ ] Nút − / + hoạt động, tôn trọng min/max/step, tự mờ ở hai đầu, spinner mặc định đã ẩn.
- [ ] Tắt JavaScript → ô số lượng **vẫn có viền**.
- [ ] Variable chưa chọn option → nút mờ + `cursor: not-allowed`, bấm vào hiện thông báo **trong
      trang**, không phải alert của trình duyệt.
- [ ] Variable: đổi variation → ô quantity bị Woo thay mới vẫn có nút − / +.

## Product Image

**Lightbox** (bật bằng **Open full screen on click**, mặc định **tắt**)
- [ ] Tắt toggle → click ảnh không xảy ra gì (hồi quy cho block đã phát hành).
- [ ] Bật → con trỏ thành `zoom-in`, click ảnh chính mở overlay toàn màn hình.
- [ ] Overlay mở đúng ảnh đang xem, đi được qua cả gallery bằng mũi tên.
- [ ] Phím ← / → chuyển ảnh, Esc đóng, click nền đóng, click ảnh thì **không** đóng.
- [ ] Mobile: vuốt trái / phải chuyển ảnh.
- [ ] Gallery chỉ có một ảnh → không hiện mũi tên.
- [ ] Ảnh trong overlay là bản **full size**, không phải bản `woocommerce_single` đang hiển thị.
- [ ] Đang chọn variation có ảnh **ngoài** gallery → overlay mở đúng ảnh variation đó (slide đầu).
- [ ] **Hồi quy:** lightbox của block **Image** và **Images Gallery** hoạt động y như trước (cả ba dùng chung `@shared/lightbox`).

**Ảnh theo variation**
- [ ] Variable: chọn đủ thuộc tính → ảnh chính đổi sang ảnh của variation đó.
- [ ] Xóa lựa chọn (Clear) → ảnh quay về ảnh đại diện ban đầu.
- [ ] Ảnh variation có trong gallery → thumbnail tương ứng sáng lên; không có → không thumbnail nào sáng.
- [ ] Đang bật autoplay → chọn variation thì autoplay dừng, Clear thì chạy lại.
- [ ] Sản phẩm simple / không có jQuery → gallery hoạt động y như trước.
- [ ] Block chỉ có **một** ảnh (không có thumbnail) → vẫn đổi theo variation.

## Product Excerpt

- [ ] `source = short` với sản phẩm không có short description → không in gì.
- [ ] `source = auto` → tự cắt từ long description.
- [ ] Short description có link + đặt word limit → link **vẫn còn** và ăn màu link / link hover.
- [ ] Cắt giữa chừng → thẻ đóng đủ, dấu "…" nằm trong thẻ chứ không rơi ra ngoài.
- [ ] Excerpt ngắn hơn giới hạn → **không** có dấu "…".
- [ ] Word limit đổi → câu mẫu trong editor dài/ngắn theo.
- [ ] Bật line-clamp → cắt đúng số dòng, không có nút mở rộng (đúng thiết kế).

## Product Description

- [ ] Không còn select Source; block luôn in long description.
- [ ] Bật clamp + Read more → nút gập/mở chạy đúng, `aria-expanded` đổi theo.
- [ ] Sản phẩm có heading/list/table trong mô tả → style lồng bên trong ăn. *(chưa test được — cần một
      sản phẩm có mô tả đủ phong phú)*
- [ ] Hover link trong mô tả đổi màu (cả editor lẫn front-end).

## Product Stock

- [ ] Đủ 4 trạng thái trên sản phẩm thật: còn hàng, hết hàng, đặt trước, sắp hết (đặt ngưỡng low-stock).
- [ ] Toolbar có **Preview state** 4 trạng thái; đổi → canvas đổi chữ, icon, màu chữ và màu nền.
- [ ] Lưu bài rồi mở lại → preview state **không** được lưu vào nội dung.
- [ ] `displayType = text` → không sinh padding/radius; `badge` → có.
- [ ] Bật số lượng còn lại trên sản phẩm có quản lý kho.

## Related Products

- [ ] Sản phẩm không có related → block không in gì.
- [ ] Đủ 5 kiểu sắp xếp (rand / date / price / popularity / rating).
- [ ] Số cột theo từng thiết bị; `relatedLayout = list` → không còn grid.
- [ ] Bật/tắt từng phần tử thẻ (ảnh, tên, giá, sao, nút).
- [ ] Nút "Add to cart" trên thẻ: simple thêm thẳng vào giỏ, variable nhảy sang trang sản phẩm.
- [ ] Hover ảnh + hover nút (kiểm cả trong editor).

## Social Share — variation "Product Share"

- [ ] Inserter hiện đủ hai mục: "Social Share" và "Product Share" — icon cũ giữ nguyên.
- [ ] Tắt WooCommerce → mục "Product Share" biến mất khỏi inserter, "Social Share" vẫn còn.
- [ ] Chọn từng variation → breadcrumb / inspector hiện đúng tên variation (kiểm `isActive`).
- [ ] Từng mạng mở đúng cửa sổ share với đúng URL + tên sản phẩm.
- [ ] Pinterest nhận được ảnh sản phẩm khi bật `includeImage`; tắt → không gửi ảnh nào.
- [ ] Đặt variation này ngoài trang sản phẩm → tự rơi về chia sẻ trang hiện tại, không biến mất.
- [ ] Đủ 4 shape + 4 hover motion.
- [ ] Bật nhãn → tên mạng hiện cạnh icon, chip tròn chuyển thành pill; tắt → vẫn có `aria-label`.
- [ ] `colorMode = official` → icon giữ màu brand, không bị tint đè.
- [ ] **Hồi quy:** block Social Share đã chèn trong bài cũ → giao diện front-end không đổi.
