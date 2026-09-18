# Flexa Block — Phạm vi sửa nội dung ngoài Front-End (Inline Editing)

Danh sách block đã cho phép click-sửa-trực-tiếp ngoài trang, và block chưa làm.
Nguồn chân lý: registry `Inline_Editor::EDITABLE` trong
`includes/class-inline-editor.php`.

## ✅ Đã cho phép sửa inline (35 loại block)

### Nhóm text đơn giản
| Block | Slug | Trường sửa được |
|---|---|---|
| Heading | `flexa/heading` | tiêu đề (`content`), phụ đề (`subheadingContent`) |
| Text | `flexa/text` | nội dung (`content`) |
| Button | `flexa/button` | nhãn nút (`text`) |
| Table of Contents | `flexa/table-of-content` | tiêu đề (`title`) |
| Progress Bar | `flexa/process-bar` | tiêu đề (`title`) |
| Before / After | `flexa/before-after` | nhãn trước (`beforeLabel`), nhãn sau (`afterLabel`) |
| Star Rating | `flexa/star-rating` | tiêu đề (`title`) |
| Modal | `flexa/modal` | nhãn nút mở (`triggerText`) — trong `<button>`, vẫn sửa được |
| Notice | `flexa/notice` | tiêu đề (`title`), nội dung (`content`) |

### Nhóm ghép nhiều trường
| Block | Slug | Trường sửa được |
|---|---|---|
| Banner | `flexa/banner` | `heading`, `description`, nhãn nút chính/phụ (`primaryText`, `secondaryText`) |
| CTA | `flexa/cta` | `heading`, `description`, `primaryText`, `secondaryText` |
| Info Box | `flexa/info-box` | `prefix`, `title`, `description`, nhãn nút (`buttonText`) |
| Testimonial | `flexa/testimonial` | `title`, trích dẫn (`quote`), tên tác giả (`authorName`), chức danh (`authorJob`) |
| Team Member | `flexa/team-member` | `name`, `role`, `bio` |

### Nhóm nhãn theo đơn vị (object-key)
| Block | Slug | Trường sửa được |
|---|---|---|
| Countdown | `flexa/countdown` | nhãn `labels.days / hours / minutes / seconds` |

### Nhóm nhãn field của form
| Block | Slug | Trường sửa được |
|---|---|---|
| Subscribe – Name | `flexa/subscribe-form-name` | nhãn (`label`) |
| Subscribe – Email | `flexa/subscribe-form-email` | nhãn (`label`) |
| Subscribe – Phone | `flexa/subscribe-form-phone` | nhãn (`label`) |
| Subscribe – Message | `flexa/subscribe-form-textarea` | nhãn (`label`) |
| Subscribe – Website | `flexa/subscribe-form-url` | nhãn (`label`) |
| Subscribe – Date | `flexa/subscribe-form-date` | nhãn (`label`) |
| Subscribe – Select | `flexa/subscribe-form-select` | nhãn (`label`) |
| Subscribe – Radio | `flexa/subscribe-form-radio` | nhãn (`label`) |
| Subscribe – Checkbox | `flexa/subscribe-form-checkbox` | nhãn (`label`) |
| Subscribe – Toggle | `flexa/subscribe-form-toggle` | nhãn (`label`) |
| Subscribe – Upload | `flexa/subscribe-form-upload` | nhãn (`label`) |

### Nhóm bảng 2 chiều
| Block | Slug | Trường sửa được |
|---|---|---|
| Comparison Table | `flexa/comparison-table` | Cột: `title`, `subtitle`, `badge`, `ctaText`; hàng: `label`; **ô giá trị** `rows[].values[col]` (chỉ ô chữ; ô ✓/✗ boolean không sửa inline) |
| Data Table | `flexa/data-table` | Nhãn cột (`columns[].label`); **ô giá trị** `rows[].values[col]` |

### Nhóm danh sách (repeater) — map an toàn qua `data-flexa-item`
| Block | Slug | Trường sửa được |
|---|---|---|
| FAQ | `flexa/faq` | `question`, `answer` |
| Tabs | `flexa/tabs` | `label`, `content` |
| Steps | `flexa/steps` | `title`, `description` |
| Timeline | `flexa/timeline` | `date`, `title`, `description` |
| Pricing Table | `flexa/pricing-table` | `name`, `badge`, giá/kỳ tháng & năm, `features` (gộp dòng), `ctaText` |
| Counter | `flexa/counter` | nhãn (`label`) |
| Icon List | `flexa/icon-list` | chữ mỗi dòng (`items[].text`) |

## ⚪ Không áp dụng

Đã phủ hết mọi block/trường **của plugin này** có thể sửa inline (block của add-on
xem mục dưới). Những mục dưới đây **không áp dụng** (không có chữ để sửa, hoặc
không phù hợp với inline text) — không nằm trong kế hoạch.

**Block không có chữ do người viết nhập:**

`flexa/container`, `flexa/grid`, `flexa/slides`, `flexa/slide` (layout);
`flexa/image`, `flexa/images-gallery`, `flexa/video-popup`, `flexa/google-map`,
`flexa/separator`, `flexa/lottie` (media/hình ảnh/animation);
`flexa/post-grid`, `flexa/rss`, `flexa/facebook-feed`, `flexa/instagram-feed`, `flexa/taxonomy` (truy vấn/nạp động — nội dung tự sinh, không phải chữ người viết gõ trong block);
`flexa/product-name`, `flexa/product-price`, `flexa/product-rating`, `flexa/product-detail`, `flexa/product-image`, `flexa/product-description`, `flexa/product-stock`, `flexa/product-excerpt`, `flexa/product-add-to-cart`, `flexa/product-meta`, `flexa/product-related` (WooCommerce — tên/giá/sao/mô tả/ảnh/tồn kho/SKU/danh mục/thẻ lấy từ sản phẩm, không phải chữ người viết gõ trong block; các chuỗi nhãn tuỳ biến của chúng dịch qua `wpml-config.xml`);
`flexa/social-icon`, `flexa/social-share`, `flexa/icon` (icon); `flexa/breadcrumb` (tự sinh);
`flexa/subscribe-form-hidden` (field ẩn, không có nhãn).

**Trường không áp dụng trong block vẫn sửa được phần chữ:**

| Block | Trường không áp dụng | Lý do |
|---|---|---|
| Counter | `number.value / prefix / suffix` | Là số, class dùng chung prefix/suffix, lại đếm-động — không hợp với inline text (nhãn `label` vẫn sửa được) |
| Comparison Table | ô giá trị `true` / `false` | Render thành icon ✓/✗ (không phải chữ) → cần toggle chứ không gõ được (ô chữ vẫn sửa được) |

## 🧩 Block của add-on

Registry không đóng: plugin khác nối thêm block của mình qua filter
`flexa_block_inline_editable` (cùng mô hình với filter catalog `flexa_block_blocks`),
nên không add-on nào phải nhân bản REST route, module front-end hay thanh toolbar.

```php
add_filter( 'flexa_block_inline_editable', function ( array $registry ): array {
    return $registry + [
        'my-plugin/my-block' => [
            'fields' => [
                [ 'attr' => 'title', 'selector' => '.my-block__title', 'allowed' => 'minimal' ],
            ],
        ],
    ];
} );
```

Mỗi entry được kiểm tra hình dạng trước khi dùng (`attr` + `selector` bắt buộc;
trường `repeat` / `object` / `cell` cần thêm `key`), entry sai bị bỏ qua thay vì
gây fatal. Trường repeater vẫn phải gọi `Inline_Editor::item_attr( $index )` trên
phần tử bọc mỗi item trong render.php, với **chỉ số gốc** trong mảng.

Danh sách trường mà **Flexa Block Pro** khai báo nằm ở
`flexa-block-pro/includes/class-inline-editing.php` — file đó cũng ghi rõ những
block/trường Pro cố ý không hỗ trợ và lý do.

## 📝 Ghi chú
- **Định dạng (cỡ chữ / màu chữ / đậm-nghiêng…) trên thanh edit: CHƯA làm** —
  đang chờ chốt hướng (áp cả element qua thuộc tính block, hay định dạng phần
  bôi đen inline).
- Quyền: Admin luôn có; Editor bật mặc định; role khác tắt (chỉnh ở tab
  **Editing** trong trang cài đặt Flexa Block).
- Mỗi lần sửa được ghi log dạng comment "note" trên bài viết.
