# MDx 2.4.0 — 页脚自定义增强

## 新增后台选项（MDx 主题 → 样式 → 页脚）

| 选项 | 字段名 | 说明 |
|------|--------|------|
| 显示主题署名 | `mdx_footer_credit_show` | 开 / 关；关闭后隐藏页脚底部「Theme: MDx By AxtonYao」整行 |
| 页脚署名文本 | `mdx_footer_credit` | 自定义该行内容，支持 HTML 与占位符；留空使用默认内容 |
| 页脚快捷链接 | `mdx_footer_links` | 每行一条 `文字\|链接`，显示在署名下方（关于 / RSS / GitHub 等） |
| 页脚附加内容 | `mdx_footer_extra` | HTML，显示在署名下方，可用于赞助、致谢、友链图标等 |

### 占位符

页脚署名文本支持以下占位符，保存后自动替换：

- `{year}` → 当前年份（如 2026）
- `{site}` → 站点名称
- `{theme}` → 主题名（MDx）

### 示例

```
© {year} {site} · Theme: MDx By <a href="https://flyhigher.top">AxtonYao</a>
```

`页脚快捷链接` 示例：

```
关于|/about/
RSS|/feed/
GitHub|https://github.com/
```

## 实现说明

- 三种页脚样式（传统 / 简单 / 现代）全部支持新字段；
- 快捷链接：带 `https://` 的链接自动 `target="_blank" rel="noopener noreferrer"`，站内相对链接保持当前窗口；
- 附加内容与署名均通过 `wp_kses_post()` 过滤，保持后台可视化编辑的同时避免不安全的 HTML；
- 兼容原有「隐藏页脚版权」机制（`mdx_hide_footer`）与页脚格言 / 一句话 API / ICP / 公安备案；
- 深色模式下由主题页脚配色自动接管，链接颜色与页脚文字一致。

## 验证

- 页脚样式 1 / 2 / 3 均正确渲染署名、快捷链接、附加内容；
- 占位符 `{year}` `{site}` 正常替换为「2026 / 我的博客」；
- 后台表单保存验证通过，前台即时生效；
- 深色模式、移动端（390px）无横向溢出；
- `debug.log` 无输出。

## 版本

- 主题版本：`2.4.0`（构建号 `2.4.0.20261018`）
