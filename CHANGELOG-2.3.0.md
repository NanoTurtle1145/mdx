# MDx 2.3.0 — MD3 重构为与 MD2 对等的完整风格选项

> 根据反馈，放弃「在页面样式上层层覆盖」的做法，改为**与 Material Design 2 完全对等的
> 独立风格选项**：后台一个开关启用整套 MD3 视觉，另一个开关启用 MD3 字体（Roboto）。
> 同时彻底保证 Banner / 特色图 / 懒加载图片的渲染逻辑不被修改。

---

## 一、后台选项（照搬 MD2 的交互）

位置：**MDx 主题 → 样式 → 全局**，紧跟在「Material Design 2 / Material Design 2 字体」之后：

| 选项 | 字段名 | 说明 |
|------|--------|------|
| Material Design 3 | `mdx_md3` | 开启后，主题将会使用 Material Design 3 风格。 |
| Material Design 3 字体 | `mdx_md3_font` | 开启后，部分标题文字将会使用 Material Design 3 风格字体（Roboto）显示。请注意该字体仅包含拉丁字符。 |

- 两个选项的文案、单选交互与 MD2 选项保持一致；
- **MD2 与 MD3 互斥**：保存其中一个为「开启」时，另一个会自动关闭；
- 默认均为「关闭」，与 MD2 一致；开启 MD3 后加载 `css/md3-tokens.css` + `css/md3.css`。

---

## 二、MD3 风格包含什么

- **动态配色**：使用 Material Color Utilities，从主题的 20 种主色、17 种强调色生成浅色 / 深色两套 MD3
  语义色（primary / secondary / tertiary / surface 系列 / outline / error 等）；后台切换主题颜色后，
  整套界面配色同步变化，深色模式自动切换对应的深色调色板。
- **界面容器**：应用栏（surface + 毛玻璃 + 滚动抬升）、抽屉（surface-container + MD3 选中态）、
  卡片 / 文章列表（surface-container、12px 圆角、悬停抬升）、页脚、正文容器。
- **控件**：按钮（胶囊 / 填充 / 文字）、图标按钮、FAB（primary-container + 16px 圆角 + 抬升）、
  输入框与评论框（MD3 描边 + 主色聚焦）、对话框（28px）、菜单（4px）、Snackbar、通知、Cookie 提示。
- **排版**：MD3 字阶（标题 / 正文 / 标签），正文 body-large 16px / 1.8 行高；`mdx_md3_font` 开启时
  标题类文字使用内置 Roboto（`fonts/Roboto-*.woff2`，仅拉丁字符，中文自动回退系统字体）。
- **圆角只出现在 MD3 规范规定的组件上**：按钮胶囊、FAB 16px、卡片 12px、对话框 28px、
  输入框 4px。整页容器、文章标题区、Banner、图片都不会被套上大圆角。

---

## 三、Banner / 图片安全

本版本 **完全不修改** 以下元素的 `background-color` / `background-image` / `display`：

- 首页大图：`.theFirstPage`、`.theFirstPageBackGround`、`.mdx-index-img-bg`、`.theFirstPageSay`
- 文章 / 页面头图：`.PostTitle`、`.PostTitle2`、`.PostTitlePage`、`.PostTitleFill`、
  `.PostTitleFill2`、`.PostTitleFillBack2`、`.PostTitleFillPage`、`.PostTitleFillPageBackGround`
- 正文图片：`.PostMainImg0`、`.PostMainImg`、`.mdx-lazyload*`、`.LazyLoadList`、`.post_list_t_img`

头图、页面头图、首页大图、列表缩略图仍由主题原有的懒加载逻辑渲染，不受 MD3 开关影响。

---

## 四、验证记录

环境：WordPress 7.1.3 + MariaDB 10.11 + PHP 8.3.35（`WP_DEBUG = true`）。

- **图片审计**：首页 5 种样式、文章 4 种样式（含特色图 Banner）、页面头图、分类页、搜索页——
  `<img>` 无加载失败、`.lazyload[data-bg]` 背景图无缺失、无内联背景被覆盖；首页 `.theFirstPage` 背景图为
  主题默认大图，文章页特色图 `naturalWidth = 1200`。
- **布局**：桌面 1440px / 移动 390px 均无横向溢出，无 JS 报错。
- **后台**：旧「MD3」标签页已移除；「全局」中的 MD3 / MD3 字体选项与 MD2 并列；表单保存验证通过，
  且开启 MD3 会自动关闭 MD2。
- **回退**：关闭「Material Design 3」后不加载 MD3 资源，页面与 2.1.x 原版一致。

---

## 五、版本

- 主题版本：`2.3.0`（构建号 `2.3.0.20261017`）
- 兼容：WordPress 6.4 ~ 7.1、PHP 7.4 ~ 8.3
- 许可证：GNU GPL v3.0
