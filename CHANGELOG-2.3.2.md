# MDx 2.3.2 — 修复 MD3 下「图片覆盖式卡片」文字看不清

## 问题现象

浅色模式下，首页/列表里带特色图的卡片，标题与摘要呈深灰/黑色，叠在深色图片遮罩上几乎看不清
（截图中的「本地部署与维护备忘」卡片即为此问题）。

## 原因

MDx 的部分文章卡片（`content-1` / `content-3` 等模板）使用 MDUI 的「图片覆盖」结构：

```html
<div class="mdui-card-media-covered ct1">
  <div class="mdui-card-primary-title">标题</div>
  <div class="mdui-card-primary-subtitle">摘要</div>
</div>
```

这层由 MDUI 定义为 `color: #fff` + 半透明黑色遮罩，文字本来就应该是**白色**。
2.3.0 的 MD3 样式为了统一卡片文字颜色，把 `.mdui-card-primary-title` / `.mdui-card-primary-subtitle`
强制设成了 MD3 的 `on-surface`（深色），于是深色文字被叠到了深色图片上。

## 修复

- 对 `.mdui-card-media-covered`（含其内部标题、摘要、链接）恢复白色文字：
  - 标题：`#fff`，并加轻微文字阴影提升可读性；
  - 摘要：`rgba(255,255,255,.78)`；
- 其它非覆盖式卡片仍使用 MD3 的 `on-surface` / `on-surface-variant`；
- 深色模式同样适用（覆盖层本身是半透明黑，白字保持可读）。

## 验证

- 首页覆盖式卡片：标题 `rgb(255,255,255)`、摘要 `rgba(255,255,255,0.78)`，遮罩 `rgba(0,0,0,0.5)`；
- 普通卡片（无图/白底）文字仍为 MD3 深色，对比度正常；
- 深色模式、移动端、分类页、搜索页正常；无 JS 报错、`debug.log` 无输出。

## 版本

- 主题版本：`2.3.2`（构建号 `2.3.2.20261017`）
