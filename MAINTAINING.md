# 维护说明（Fork 维护版）

本仓库是 [MDx](https://github.com/yrccondor/mdx)（作者 [AxtonYao](https://flyhigher.top)，GPLv3）的维护 Fork。
上游已停止更新，本 Fork 负责 **兼容最新 WordPress / PHP** 并持续增加自定义能力。

- Fork 地址：<https://github.com/NanoTurtle1145/mdx>
- 上游地址：<https://github.com/yrccondor/mdx>

## 分支与远端

| 远端 | 地址 | 用途 |
|------|------|------|
| `origin` | `git@github.com:NanoTurtle1145/mdx.git` | 本 Fork，日常开发与发布 |
| `upstream` | `git@github.com:yrccondor/mdx.git` | 上游，仅用于同步参考 |

分支约定：

- `master`：维护主线，即发布版本所在分支；所有开发直接提交到 `master`（个人维护项目，不强制 PR 流程）。
- 发布使用 tag：`vX.Y.Z`，推送 tag 后 GitHub Actions 自动构建 `mdx-X.Y.Z.zip` 并发布 Release。

## 与上游同步

上游自 2024-11 后基本不再更新，如需同步：

```bash
git fetch upstream
git merge upstream/master
```

冲突处理优先级（**不要破坏以下行为**）：

1. **Banner / 头图安全**：不得覆盖 `.PostTitle*`、`PostTitleFill*`、`theFirstPage*`、`mdx-index-img-bg`、`PostMainImg*`、`.lazyload` 等元素的 `background-color` / `background-image`。
2. **MD2 / MD3 选项对等**：两者互斥，UI 与文案保持一致的「开启/关闭」模式；MD3 独立 CSS（`css/md3-tokens.css` + `css/md3.css`），MD2 关闭时不影响 MD2。
3. **MD3 不重绘应用栏**：顶栏保持主题原有行为（首页/文章页透明、列表页主题色、白色文字），只做字号与状态层处理。
4. **不套用全站暴力圆角**：圆角只用于 MD3 组件（按钮、FAB、卡片、对话框、输入框），不改页面结构容器。

## 构建与验证

MD3 设计令牌由脚本生成（`@material/material-color-utilities`）：

```bash
npm install
NODE_PATH=./node_modules npm run build:md3   # 生成 css/md3-tokens.css
```

发布前检查：

```bash
# PHP 语法（工作流会跑 7.4 ~ 8.3）
find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l

# 本地站点验证（docker 部署见部署仓库/本地脚本）
# - 首页 / 文章页 / 分类页 / 搜索 / 页面 / 深色模式 / 移动端
# - 确认 banner、特色图、分享按钮、阅读进度、页脚均正常
# - wp-content/debug.log 无输出
```

## 发布流程

```bash
# 1. 更新版本号
#    style.css: Version: X.Y.Z
#    includes/admin_init_ver.php: X.Y.Z.YYYYMMDD
#    package.json
# 2. 更新 README.md 与 CHANGELOG-X.Y.Z.md
# 3. 提交并推送
git add -A && git commit -m "chore(release): X.Y.Z"
git push origin master

# 4. 打 tag 并推送（触发自动构建与 Release）
git tag -a vX.Y.Z -m "MDx X.Y.Z"
git push origin vX.Y.Z
```

## 版本记录

| 版本 | 主要变更 |
|------|----------|
| 2.0.4 | 上游最后版本 |
| 2.1.0 | 兼容最新 WordPress / PHP 7.4~8.3，清理遥测与无用依赖，新增 13 个增强选项 |
| 2.2.0 / 2.2.1 | 首次 MD3 风格实验（后由 2.3.0 重构） |
| 2.3.0 | MD3 重构为与 MD2 对等的完整风格选项 + 动态设计令牌 |
| 2.3.1 | 修复 MD3 顶栏与文章头图断层 |
| 2.3.2 | 修复图片覆盖式卡片文字看不清 |
| 2.3.3 | 修复正文卡片文字贴边 |
| 2.3.4 | 修复分享按钮裁切，恢复内置环形阅读进度 |
| 2.3.5 | 回到顶部保持 MD3 圆角方形，阅读进度改为顶部线性进度条 |
| 2.3.6 | 修复深色模式 H1/H2/H3 被主题反相滤镜变黑 |
| 2.4.0 | 页脚自定义增强（署名/快捷链接/附加内容/显示开关） |

详细变更见各 `CHANGELOG-*.md`。

## 许可证

沿用上游 GPLv3。分发时需保留原作者的版权与许可证信息（`LICENSE`、页面底部的主题署名可在后台自定义）。
