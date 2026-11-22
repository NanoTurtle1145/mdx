# MDx 2.5.0 — MD3 风格「浓厚化」增强

在不改动 Banner / 头图 / 顶栏行为的前提下，让 MD3 风格更完整、细节更到位。

## 新增内容

### 1. 标签与文章元信息
- 文章标签改为 **MD3 outlined chips**（圆角小标签，悬停主题色描边）；
- 文章底部日期改为 **label chip**（带日历/编辑图标）；
- 面包屑改为 MD3 label 文本，当前页加粗、分隔符降低对比度；
- 文章底部信息条改为 `surface-container-low` 色带，不再使用主题旧的灰色块。

### 2. 评论区
- 每条评论变成 **MD3 surface 卡片**（`surface-container-low`、12px 圆角、内边距）；
- 头像统一 44px 圆形；
- 作者名使用 title-small，博主徽章改为 primary-container 小圆角标签；
- 回复按钮改为 MD3 text button（主题色、圆角状态层）；
- 评论分页改为 **圆形页码按钮**，当前页 primary 填充。

### 3. 上一篇 / 下一篇
- 改为 MD3 tonal 工具条：`surface-container-low` 背景 + outline-variant 分割线；
- 方向文字 label-medium，标题 title-medium；
- 箭头图标使用 primary 色。

### 4. 首页 / 标签云
- 首页卡片「阅读文章」按钮改为 **MD3 tonal button**（secondary-container）；
- 标签云页面的标签改为 MD3 chips。

### 5. 页脚 / 抽屉
- 页脚快捷链接改为 MD3 chips（胶囊描边，悬停主题色）；
- 页脚增加上下留白；
- 抽屉导航项改为 46px 高、full 圆角，激活项为 secondary-container 胶囊，图标随状态变色。

### 6. 正文排版节奏
- h2/h3 增加段前距，正文层级更清晰；
- h5/h6 使用 MD3 typescale；
- `hr`、表格单元格、有序/无序列表 marker 使用 MD3 颜色；
- 正文链接增加 hover 过渡。

### 7. 其他
- 填充按钮增加 MD3 状态层（8% on-primary 叠加）；
- 卡片操作区信息文字/图标使用 label-medium 与 on-surface-variant。

## 严格保留（未改动）

- `PostTitle*`、`PostTitleFill*`、`theFirstPage*`、`mdx-index-img-bg`、`PostMainImg*` 等 Banner / 头图层的背景与图片；
- 顶栏「首页/文章页透明 + 白字、列表页主题色 + 白字」的行为；
- 分享按钮仍完整悬浮在卡片左侧外；
- 阅读进度条、回到顶部 FAB、MD2 / MD3 选项互斥逻辑。

## 验证

- 桌面 1396px / 移动 390px 均无横向溢出；
- 浅色 / 深色模式：chips、评论卡片、页脚 chips、信息条颜色均取自 MD3 语义色；
- 图片 0 损坏，Banner 图层背景保持不变；
- 无 JS 报错，`debug.log` 无输出。

## 版本

- 主题版本：`2.5.0`（构建号 `2.5.0.20261122`）
