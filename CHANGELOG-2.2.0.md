# MDx 2.2.0 — Material Design 3（MD3）外观适配

> 基于 [mdui.org](https://www.mdui.org/zh-cn/) 的 **Material Design 3 / Material You** 设计规范与设计令牌，
> 为 MDx 增加了一层可开关的 MD3 外观，同时保留原有 MD2 风格与全部交互逻辑。
>
> 环境：WordPress 7.1.3 + PHP 8.3.35（`WP_DEBUG = true`）实测通过。

---

## 一、这次新增了什么

### 1. MD3 设计令牌（Design Tokens）

在 `<html class="mdx-md3">` 上按 mdui 2 文档的命名方式提供完整令牌：

| 类别 | 变量 | 说明 |
|------|------|------|
| 颜色 | `--mdui-color-primary`、`--mdui-color-on-surface`、`--mdui-color-surface-container-*` 等 | 依据主题主色自动生成，浅色 / 深色各一套 |
| 圆角 | `--mdui-shape-corner-none/extra-small/small/medium/large/extra-large/full` | 4 / 8 / 12 / 16 / 28px |
| 排版 | `--mdui-typescale-display/headline/title/body/label-*` | MD3 15 级字阶 |
| 状态层 | `--mdui-state-layer-hover/focus/pressed/dragged` | 0.08 / 0.12 / 0.12 / 0.16 |
| 抬升 | `--mdui-elevation-level0` ~ `level5` | MD3 阴影规范 |
| 动效 | `--mdui-motion-easing-*`、`--mdui-motion-duration-*` | 标准缓动与时长 |
| 断点 | `--mdui-breakpoint-xs/sm/md/lg/xl` | 0 / 600 / 840 / 1200 / 1600 |

### 2. 动态配色（20 种主色 + 17 种强调色）

使用与 mdui 2 / Material You 相同的 **Material Color Utilities** 算法，从主题的每种主色生成完整的
Material 3 调色板（primary / secondary / neutral / neutral-variant 等），并自动映射为语义色：

- 主色 → `primary`、`primary-container`、`on-primary`…
- 强调色 → `tertiary`、`tertiary-container`…
- 中性色 → `surface`、`surface-container-lowest ~ highest`、`outline`、`outline-variant`…

因此后台切换「主题颜色 / 强调色」时，MD3 的整套配色（包括卡片、抽屉、按钮、FAB、对话框）会同步变化。
深色模式会根据同一套调色板自动生成对应的深色语义色。

### 3. 组件视觉映射

在**不改变主题原有交互逻辑**的前提下，把 MDUI 1 的结构映射到 MD3 视觉：

- **顶部应用栏**：surface 背景 + 92% 透明度 + 背景模糊，滚动时使用 MD3 elevation level2
- **抽屉导航**：surface-container-low 背景、16px 圆角、MD3 药丸形导航项（选中态为 secondary-container）
- **按钮 / 图标按钮 / FAB**：药丸形按钮、圆形状态层、FAB 使用 primary-container 与 level3 抬升
- **卡片 / 文章列表**：12px 圆角、surface-container-low / lowest 填充、悬停抬升
- **文章页**：标题区使用 primary-container（28px 顶部圆角），正文卡片使用 surface-container-lowest，
  正文排版为 MD3 body-large，支持可选衬线阅读字体
- **输入框 / 评论**：MD3 outlined 文本框、聚焦态主色描边
- **对话框 / 菜单 / Snackbar**：28px / 4px 圆角、surface-container-high / inverse-surface
- **标签云、友情链接、时间轴、短代码组件**：统一到 MD3 圆角与语义色
- **登录页**：surface 背景、16px 圆角表单卡片、MD3 填充按钮

### 4. 新增后台选项（样式 → MD3）

| 选项 | 字段名 | 取值 | 说明 |
|------|--------|------|------|
| MD3 外观 | `mdx_md3` | 开 / 关 | 总开关，关闭后完全回到原 MD2 风格 |
| MD3 圆角风格 | `mdx_md3_round` | small / default / large | 8·12·20 / 4·12·16·28 / 16·20·32px |
| 正文阅读字体 | `mdx_md3_reading_font` | sans / serif | 仅影响文章正文阅读字体 |
| 色调表面 | `mdx_md3_tonal` | 开 / 关 | 开：表面带主色色调（Material You 观感）；关：中性灰白 |

### 5. 兼容性

- 与「深色模式」「OLED 纯黑」「白色主题」「MD2 风格」选项兼容；开启 MD3 时自动停用 MD2 样式层。
- 夜晚模式、系统深色模式、每篇文章单独配色（文章设置里的主题色）都会驱动 MD3 令牌切换。
- 移动端（390px）与桌面端（1440px）均无横向溢出；4 种首页样式、4 种文章样式、宽列表布局均通过检查。
- 关闭 MD3 后页面加载的 CSS/类名与 2.1.0 完全一致，可随时回退。

---

## 二、实现方式

```
css/md3-tokens.css        由构建脚本生成：20 主色 + 17 强调色 × 浅色/深色的颜色令牌（约 64KB）
css/md3.css               MD3 设计令牌（圆角/排版/状态层/抬升/动效）+ 组件视觉映射（约 58KB）
tools/build-md3-tokens.js 配色生成脚本（Material Color Utilities）
includes/mdx-extra.php    <html> 类名、OLED / 无色调表面运行时变量
functions.php             资源加载、登录页资源、MD2 互斥
admin_style.php           新增「MD3」选项卡与保存逻辑
```

如需修改主题色对应的 MD3 调色板，修改 `tools/build-md3-tokens.js` 中的色值后执行：

```bash
npm install --no-save @material/material-color-utilities@0.2.7
npm run build:md3     # 或：node tools/build-md3-tokens.js
```

---

## 三、验证记录

- 环境：WordPress 7.1.3 + MariaDB 10.11 + PHP 8.3.35，`WP_DEBUG = true`
- 开启 MD3 后：首页 / 文章 / 页面 / 分类 / 搜索 / 404 / Feed / REST 均 200，`debug.log` 无输出。
- 逐项检查：
  - 主色 indigo 下 `surface = rgb(251,248,253)`、`primary-container = rgb(222,224,255)`、卡片圆角 12px、FAB 16px；
  - 深色模式：`surface = rgb(19,19,22)`、正文 `on-surface = rgb(228,225,230)`、标题区为深色 primary-container；
  - 圆角 small / large 分别生效（卡片 8 / 20px）；
  - 衬线阅读字体生效（正文 font-family: Georgia）；
  - 关闭色调表面后表面为中性灰白；OLED 模式深色表面为纯黑；
  - 后台「MD3」选项卡可正常保存 4 个选项；登录页、MD3 关闭回退均正常；
  - 4 种首页样式、4 种文章样式、宽列表、移动端均无横向溢出、无 JS 报错。

---

## 四、已知限制 / 后续可做

- 本次为 **MD3 视觉层适配**：保留 MDUI 1 的 DOM 结构与交互（抽屉、菜单、tooltip、搜索动画等），
  通过设计令牌与组件样式映射实现 MD3 外观，因此不需要重写模板和主题 JS。
- 若要进一步把底层组件替换为 [mdui 2](https://github.com/zdhxiong/mdui) 的 Web Components（原生 MD3 组件），
  需要重写 45 个模板中的约 1,600 处 `mdui-*` 类名、主题 JS 中的 MDUI 1 API 调用，并重新编译前端资源，
  属于独立的大版本迁移，建议分阶段进行。
- 动态取色（从特色图片提取主色）暂未接入，可按需使用 mdui 2 的 `getColorFromImage` 思路扩展。

---

## 五、版本

- 主题版本：`2.2.0`（构建号 `2.2.0.20261017`）
- 兼容：WordPress 6.4 ~ 7.1、PHP 7.4 ~ 8.3
- 许可证：GNU GPL v3.0（保留原作者 Axton Yao 的版权与 LICENSE）
