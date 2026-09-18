# Đối chiếu block WooCommerce trong bộ template

> Nguồn: 7 template `flexa_template` trên site **wptemplate.local** (đọc thẳng DB, cổng MySQL 10053).
> Đối chiếu với `src/blocks/` của flexa-block bản dev. Ngày 2026-09-18.
>
> Site đó đang chạy **flexa-block 1.0.10** (chỉ có 5 block product đã phát hành) + WooCommerce +
> flexa-block-pro + flexa-wishlist-for-woocommerce, theme `flexa-store`.

---

## Kết luận một dòng

Bộ template **không dùng block single-product nào cả**. Toàn bộ 10 block WooCommerce xuất hiện đều
nằm **bên trong một vòng lặp sản phẩm** (`product-collection` → `product-template`). Thứ Flexa thiếu
không phải mấy block lẻ — mà là **chính cái vòng lặp**.

Đây đúng là mục 4 trong kế hoạch hợp nhất (`flexa/product-loop`), và giờ có bằng chứng thật về việc
nó được dùng như thế nào.

👉 **Muốn bỏ hẳn block của Woo thì cần đúng 3 block mới + 2 phần mở rộng** — xem §6 bên dưới.

---

## 1. Template nào dùng Woo

| Template | ID | Dùng block Woo |
|---|---|---|
| Lumio SaaS | 151 | — |
| Axiom SaaS | 429 | — |
| Marlo Fintech | 3347 | — |
| Pulse | 3389 | — |
| **Sill** | 3935 | ✔ 2 vòng lặp |
| **Maison Verte** | 5382 | ✔ 1 vòng lặp |
| **Terrain** | 12362 | ✔ 1 vòng lặp |

3 trong 7 template là template thương mại điện tử; 4 cái còn lại (SaaS / fintech) không đụng tới Woo.

## 2. Block Woo đang dùng — và Flexa có tương đương chưa

| Block WooCommerce | Số lần | Template | Flexa có? |
|---|---|---|---|
| `woocommerce/product-collection` | 4 | Sill, Maison Verte, Terrain | ❌ **chưa có** |
| `woocommerce/product-template` | 4 | Sill, Maison Verte, Terrain | ❌ **chưa có** |
| `woocommerce/product-collection-no-results` | 2 | Sill, Terrain | ❌ **chưa có** |
| `woocommerce/product-sale-badge` | 3 | Sill, Maison Verte, Terrain | ❌ **chưa có** |
| `woocommerce/product-button` | 1 | Terrain | ⚠️ chỉ có bên trong `product-related`, không đứng riêng |
| `woocommerce/product-image` | 4 | Sill, Maison Verte, Terrain | ⚠️ `flexa/product-image` là **gallery single-product**, không phải ảnh thẻ trong lưới |
| `woocommerce/product-price` | 4 | Sill, Maison Verte, Terrain | ✅ `flexa/product-price` |
| `woocommerce/product-rating` | 2 | Sill, Terrain | ✅ `flexa/product-rating` |
| `woocommerce/product-rating-stars` | 1 | Maison Verte | ✅ `flexa/product-rating` (`displayType: stars`) |
| `woocommerce/product-average-rating` | 1 | Maison Verte | ✅ `flexa/product-rating` (`displayType: number`) |

Kèm hai block **core** cũng đứng trong vòng lặp, đóng vai trò dữ liệu sản phẩm:

| Block core | Số lần | Vai trò trong thẻ | Flexa có? |
|---|---|---|---|
| `core/post-title` (`__woocommerceNamespace: product-title`) | 1 | Tên sản phẩm, có link | ✅ `flexa/product-name` |
| `core/post-terms` (`term: product_cat`, `term: pa_colour`) | 2 | Danh mục / thuộc tính | ⚠️ `flexa/product-field` làm được `product_cat` và `product_tag`, **chưa làm được thuộc tính** (`pa_*`) |

## 3. Cấu trúc thật của một thẻ sản phẩm

Đây là thứ cần nhìn kỹ — nó cho biết `product-loop` phải chứa được những gì:

```
product-collection            ← query + layout lưới
└─ product-template           ← vòng lặp, mỗi vòng dựng một thẻ
   ├─ product-image           ← ảnh thẻ, có thể bọc sale badge bên trong
   │  └─ product-sale-badge
   ├─ core/post-terms         (product_cat)
   ├─ core/post-title         (level 3, isLink)
   ├─ product-price
   ├─ product-rating-stars
   ├─ product-average-rating
   ├─ core/post-terms         (pa_colour)
   └─ product-button          ← add to cart trên thẻ
└─ product-collection-no-results   ← nội dung khi không có sản phẩm
```

Mỗi block con đều mang `"isDescendentOfQueryLoop": true` — Woo dùng cờ này để biết đang ở trong vòng
lặp chứ không phải trang sản phẩm.

## 4. Cấu hình `product-collection` được dùng

| Template | Collection | perPage | Cột | Lọc thêm |
|---|---|---|---|---|
| Sill | `on-sale` | 1 | 1 | `woocommerceOnSale: true`, lọc tồn kho |
| Sill | `featured` | 4 | 4 | `featured: true`, lọc tồn kho |
| Maison Verte | `featured` | 8 | 4 | `featured: true`, lọc tồn kho |
| Terrain | `featured` | 8 | 4 | `featured: true`, lọc tồn kho |

Nhận xét cho thiết kế `product-loop`:

- **`featured` và `on-sale` là hai collection thực sự được dùng** — nên là hai giá trị `source` đầu
  tiên phải có, đứng trước `related` / `best-selling` / `newest` trong bảng kế hoạch.
- `displayLayout: flex` với `columns` 1 và 4, `shrinkColumns: true` (co lại trên màn nhỏ).
- **Lọc theo tồn kho** (`instock` / `outofstock` / `onbackorder`) xuất hiện ở cả 4 vòng lặp — kế
  hoạch hiện tại chưa có.
- `inherit: false` ở mọi chỗ: không cái nào kế thừa query của trang archive, tất cả là query riêng.
- Không template nào dùng `handPicked`, `taxQuery` hay phân trang.

## 5. Điểm quan trọng: block của Flexa **chạy được** trong vòng lặp của Woo

`product-template` gọi `$query->the_post()` mỗi vòng, và WooCommerce hook
`add_action( 'the_post', 'wc_setup_product_data' )` (`includes/wc-template-functions.php:175`), nên
global `$product` **được đặt lại theo từng sản phẩm** trong vòng lặp.

`Woo_Helpers::current_product()` ưu tiên đọc đúng global đó ⇒ có thể kéo `flexa/product-price`,
`flexa/product-name`, `flexa/product-rating`, `flexa/product-field`… vào thẳng trong
`woocommerce/product-template` và chúng sẽ hiển thị đúng sản phẩm của từng vòng.

Hệ quả:

- Khoảng trống **không phải** "block của ta không dùng được trong lưới".
- Khoảng trống là **ta không có cái vòng lặp**, nên người dựng template buộc phải mượn
  `product-collection` + `product-template` của Woo, rồi dùng luôn block con của Woo cho tiện.
- Nghĩa là `flexa/product-loop` có thể ra mắt mà **không cần viết lại** price / name / rating —
  chỉ cần cho phép chúng làm block con.

⚠️ **Chưa kiểm chứng thực tế.** Đây là suy luận từ đọc code của Woo, chưa cắm thử block Flexa vào
trong `product-template` trên trang thật. Phải thử trước khi dựa vào nó để thiết kế.

## 6. Danh sách block cần có để thay thế hoàn toàn Woo

Mục tiêu: dựng lại được 3 template thương mại điện tử mà **không dùng block nào của WooCommerce**.

### Đã có, dùng lại nguyên — không phải viết gì

| Cần cho thẻ | Block Flexa | Đủ chưa |
|---|---|---|
| Tên sản phẩm có link | `flexa/product-name` | ✅ có sẵn `linkToProduct` + `linkTarget` + `htmlTag` |
| Giá | `flexa/product-price` | ✅ |
| Sao đánh giá / điểm số | `flexa/product-rating` | ✅ `displayType` phủ cả `product-rating-stars` lẫn `product-average-rating` |
| Danh mục trên thẻ | `flexa/product-field` (variation Categories) | ✅ |

### Phải xây mới — đây là danh sách tối thiểu

| # | Block | Thay cho | Phải làm được gì (lấy từ template thật) |
|---|---|---|---|
| 1 | **`flexa/product-loop`** | `product-collection` + `product-template` + `product-collection-no-results` | Query theo `featured` / `on-sale`; lọc trạng thái tồn kho; số sản phẩm 1 / 4 / 8; lưới flex 1 và 4 cột, co lại trên màn nhỏ; **InnerBlocks = mẫu thẻ**, lặp mỗi sản phẩm một lần; câu thông báo khi không có sản phẩm |
| 2 | **`flexa/product-featured-image`** | `woocommerce/product-image` (trong vòng lặp) | Ảnh đại diện, tỉ lệ khung (`4/5`), `scale: cover`, link về sản phẩm, **chỗ đặt badge đè lên** |
| 3 | **`flexa/product-sale-badge`** | `woocommerce/product-sale-badge` | Chỉ hiện khi đang giảm giá; vị trí trái / phải; nền + màu chữ + bo tròn (Maison Verte dùng `999px`, cỡ chữ 10.5px, letter-spacing) |
| 4 | **Chế độ "nút trên thẻ" cho `flexa/product-add-to-cart`** | `woocommerce/product-button` | Block hiện tại in **form single-product** (có ô số lượng). Trên thẻ cần bản gọn: nút ajax add-to-cart như `woocommerce_template_loop_add_to_cart` |
| 5 | **Loại field `attribute` trong `flexa/product-field`** | `core/post-terms` với `pa_colour` | Chọn taxonomy thuộc tính (`pa_*`) và in term của nó |

**Tổng: 3 block mới + 2 phần mở rộng.** Không cần viết lại price / name / rating.

### Bản đồ thay thế 1–1

```
woocommerce/product-collection          →  flexa/product-loop
└─ woocommerce/product-template         →  (InnerBlocks của product-loop)
   ├─ woocommerce/product-image         →  flexa/product-featured-image
   │  └─ woocommerce/product-sale-badge →  flexa/product-sale-badge
   ├─ core/post-terms (product_cat)     →  flexa/product-field  (variation Categories)
   ├─ core/post-title (isLink)          →  flexa/product-name   (linkToProduct)
   ├─ woocommerce/product-price         →  flexa/product-price
   ├─ woocommerce/product-rating        →  flexa/product-rating (displayType: both)
   ├─ woocommerce/product-rating-stars  →  flexa/product-rating (displayType: stars)
   ├─ woocommerce/product-average-rating→  flexa/product-rating (displayType: number)
   ├─ core/post-terms (pa_colour)       →  flexa/product-field  (field: attribute)   ← cần làm
   └─ woocommerce/product-button        →  flexa/product-add-to-cart (chế độ thẻ)    ← cần làm
woocommerce/product-collection-no-results → thuộc tính `emptyText` của product-loop
```

### Ba quyết định thiết kế nên chốt trước

**a) `product-loop` phải composable, không được hard-code thẻ.** `flexa/product-related` hiện tại dựng
thẻ bằng code cứng (`__image` / `__title` / `__price` / `__rating` / `__button`) và bật tắt bằng toggle.
Ba template thật cho thấy mỗi cái một kiểu thẻ khác nhau (Maison Verte còn chèn cả
`flexa-wishlist/button` và hai dòng taxonomy) — toggle không bao giờ đủ. Phải là InnerBlocks như
`product-meta`.

**b) Ảnh thẻ là block riêng, không phải chế độ của `flexa/product-image`.** Theo đúng ranh giới đã
ghi trong roadmap (*gộp khi dùng chung cả bề mặt style lẫn hình dạng markup*): `product-image` là
gallery có carousel thumbnail, lightbox, đổi theo variation — markup khác hẳn một ô ảnh trong lưới,
và nặng hơn nhiều (6 KB view.js). Nhét chế độ thẻ vào đó là biến nó thành cái sọt.

**c) "Không có sản phẩm" để là thuộc tính, không phải vùng InnerBlocks thứ hai.** Cả hai template
dùng đúng một đoạn văn căn giữa ("The shelf is empt…"). Một `emptyText` + căn lề là đủ, và đơn giản
hơn hẳn việc dựng hai vùng con có tên trong Gutenberg. Nếu sau này cần đặt ảnh / nút vào đó thì mới
nâng thành InnerBlocks.

### Thứ tự làm

1. `product-loop` (có `featured` + `on-sale` + lọc tồn kho + `emptyText`) — mở khoá cả 3 template.
2. `product-featured-image` + `product-sale-badge` — hai thứ này luôn đi cùng nhau trong thẻ.
3. Chế độ thẻ cho `product-add-to-cart` — chỉ Terrain cần.
4. Field `attribute` cho `product-field` — chỉ Maison Verte cần; cũng chính là một phần của mục 14
   "Additional Information" trong roadmap.

Sau bước 1–2 là dựng lại được Sill; thêm bước 3 là xong Terrain; thêm bước 4 là xong Maison Verte.

### Còn một thứ ngoài tầm flexa-block

Maison Verte dùng `flexa-wishlist/button` — thuộc plugin **flexa-wishlist-for-woocommerce**, không
phải việc của plugin này. Chỉ cần `product-loop` cho phép block lạ làm con (không khoá
`allowedBlocks` quá chặt) thì nó vẫn dùng được bình thường.

---

## 7. Cách kiểm chứng lại

```bash
# Cổng MySQL của site lấy từ %APPDATA%\Local\sites.json (wptemplate = 10053).
# Chạy bằng PHP của Local, không dùng php hệ thống.
PHPBIN="…/lightning-services/php-8.2.29+0/bin/win64/php.exe"
EXT="…/lightning-services/php-8.2.29+0/bin/win64/ext"
"$PHPBIN" -d extension_dir="$EXT" -d extension=mysqli -d extension=mbstring scan.php
```

```sql
-- Đếm block theo loại trong toàn bộ template
SELECT ID, post_title, post_content FROM wp_posts WHERE post_type = 'flexa_template';
-- rồi regex: <!--\s*wp:([a-z0-9-]+/[a-z0-9-]+)
```

Lưu ý khi đọc: `post_content` của template lưu dấu `\/` bị escape, phải `str_replace('\\/', '/', …)`
trước khi khớp regex, nếu không sẽ bỏ sót block.
