# MDx 2.1.0 — 新版 WordPress 兼容化 + 自定义选项增强

> 本版本基于上游 [yrccondor/mdx](https://github.com/yrccondor/mdx) v2.0.4（GPLv3）定制。
> 目标环境：**WordPress 6.4 ~ 7.1**、**PHP 7.4 ~ 8.3**。
> 实测环境：WordPress **7.1.3** + MariaDB 10.11 + **PHP 8.3.35**（`WP_DEBUG = true`）。

---

## 一、安装包

- 安装包：`mdx-2.1.0.zip`（内层目录为 `mdx/`，可直接在「外观 → 主题 → 上传主题」安装）
- 主题版本：`2.1.0`（构建号 `2.1.0.20261017`）
- 兼容声明：`Requires at least: 6.4`、`Tested up to: 7.1`、`Requires PHP: 7.4`

---

## 二、兼容性修复清单

| # | 问题 | 原因 | 修复方式 |
|---|------|------|----------|
| 1 | 选项存储互相覆盖、自定义设置丢失 | `$GLOBALS['mdx_all_options']` 只含当前请求读过的键，写入时整体覆盖数据库 | 写入前重新读取数据库并 `array_merge`；对 `get_option()` 返回 `false` 的情况做 `is_array()` 保护（同时修掉 PHP 8.1+ 的 `false` 自动转数组 deprecated 警告） |
| 2 | 每个页面都触发 `_load_textdomain_just_in_time` 提示（WP 6.7+） | 主题在 `after_setup_theme` 之前调用 `__()`（菜单名、侧边栏名、默认值） | 默认值改用安全的 `mdx_default_text()`（过早调用时返回原文）；导航菜单与侧边栏注册移入 `after_setup_theme` 回调 |
| 3 | 首次激活产生 SSL 警告、后台变慢 | 首次激活会向已失效的 `mdxupdate.flyhigher.top` 发送统计请求 | 删除统计/密钥逻辑，改为纯本地初始化，并在首次加载时**一次性**写入全部默认值 |
| 4 | 后台「关于」页可能卡住数十秒 | 使用 `file_get_contents()` 同步请求已失效的 `mdxupdate.flyhigher.top` 与 jsdelivr，默认超时 60s | 移除全部远程请求，改为显示本地版本信息；同时去掉写死主题目录名 `/mdx/footer.php` 的版权检查 |
| 5 | 定制版被上游更新覆盖 | 内置 Plugin Update Checker v4.9 指向已停维护的 `info.json` | 移除 PUC 及 `plugin-update-checker/` 目录，去除后台“发现新版本”提示；如需自动更新请自行恢复并指向自己的发布源 |
| 6 | 可能 `headers already sent` | `header.php` 文件开头带 UTF-8 BOM | 删除 BOM |
| 7 | PHP 8.x 大量 `Undefined array key` 警告 | 元框保存、友情链接 nofollow、图片/头像懒加载解析未做键判断 | 全部加 `isset()` / 空合并；`save_post` 增加自动保存、修订版本、权限守卫；元框改用标准 `add_meta_boxes` 钩子 |
| 8 | `Attempt to read property "comment_parent" on null` | WP 7.x 调用 `comment_text` 过滤器时可能不传评论对象 | 增加 `WP_Comment` 类型判断 |
| 9 | 经典主题缺少现代特性声明 | 主题未声明 HTML5、feed 链接等 | 补充 `html5`、`automatic-feed-links`、`responsive-embeds`；保留 `title-tag` 与 SEO 插件兼容逻辑 |
| 10 | 未启用 mbstring 时摘要功能报错 | 直接调用 `mb_strimwidth()` | 检测函数存在性，缺失时回退 `wp_html_excerpt()` |
| 11 | 激活时 include 不存在的文件 | 引用了仓库中不存在的 `includes/admin_init_style.php` | 移除该引用 |
| 12 | 升级后已有设置被默认值覆盖 | 初始化函数直接 `update_option(defaults)` | 改为与已有选项合并写入 |
| 13 | 开启 CDN 后加载到旧版样式 | 主题样式从 jsdelivr 上游加载 | `style.css` 始终从本地主题目录加载（其余静态资源仍可使用 CDN） |

---

## 三、新增的 13 个自定义选项

### 3.1 阅读增强（后台：**MDx 主题 → 功能 → 阅读增强**）

| 选项 | 字段名 | 取值 | 作用 |
|------|--------|------|------|
| 每页文章数 | `mdx_posts_per_page` | 整数，`0` = WordPress 默认 | 作用于首页、分类、标签、搜索等主循环 |
| 首页排除分类 | `mdx_home_exclude_cats` | 分类 ID，逗号分隔 | 这些分类的文章不出现在首页（不影响分类页/搜索）；后台会列出可用分类 ID |
| 摘要字数 | `mdx_excerpt_length` | 整数，`0` = 各布局默认 | 统一覆盖文章列表摘要长度（原有 120~450 字的多套预设保持不变，仅在此项 > 0 时覆盖） |
| 字数与阅读时长 | `mdx_reading_meta` | 开 / 关 | 文章正文末尾显示「全文约 N 字 · 预计阅读 N 分钟」（中文 300 字/分、英文 200 词/分） |
| 文章版权协议 | `mdx_license` | 不显示 / CC BY / CC BY-SA / CC BY-ND / CC BY-NC / CC BY-NC-SA / CC BY-NC-ND / CC0 / 自定义 | 文章末尾输出带 Creative Commons 链接的版权声明 |
| 自定义版权文本 | `mdx_license_custom` | HTML | 版权协议选择「自定义」时生效 |
| 外链在新窗口打开 | `mdx_external_link_blank` | 开 / 关 | 正文中站外 http(s) 链接自动加 `target="_blank"` 与 `rel="noopener noreferrer"`，站内链接/锚点/mailto 不动 |
| 关闭全站评论 | `mdx_close_comments` | 开 / 关 | 隐藏评论列表、评论表单与评论数；不修改数据库，关闭后立即恢复 |

### 3.2 外观增强（后台：**MDx 主题 → 样式 → 外观增强**）

| 选项 | 字段名 | 取值 | 作用 |
|------|--------|------|------|
| 自定义 CSS | `mdx_custom_css` | CSS 文本 | 在主题样式之后以内联样式输出；保存时过滤 `<style>`/`<script>` 标签，无法注入脚本 |
| 禁用表情符号脚本 | `mdx_disable_emoji` | 开 / 关 | 移除 WP 的 emoji 检测脚本/样式与静态化过滤器 |
| 禁用 oEmbed 嵌入脚本 | `mdx_disable_embeds` | 开 / 关 | 移除 oEmbed 发现链接与前端 embed 脚本（若大量使用自动嵌入请勿开启） |
| 登录页背景图 | `mdx_login_bg` | 图片 URL | `wp-login.php` 全屏背景（带半透明遮罩） |
| 登录页 Logo | `mdx_login_logo` | 图片 URL | 替换登录页 WordPress Logo，点击返回站点首页 |

### 3.3 实现位置

- 默认值：`includes/default_value.php`
- 前端/登录页行为：**新增文件** `includes/mdx-extra.php`
- 后台表单与保存：`admin_fn.php`（阅读增强）、`admin_style.php`（外观增强）
- 读取沿用主题原有 `mdx_get_option()`，无需迁移数据；从 2.0.4 升级后新选项自动使用默认值

---

## 四、安装与升级

### 全新安装
1. 后台「外观 → 主题 → 上传主题」，选择 `mdx-2.1.0.zip`；
2. 启用后进入「MDx 主题 → 样式 / 功能」按需配置。

### 从 2.0.4 升级
- 直接上传新包并「替换当前版本」即可，已有的 `mdx_all_options` 设置会**原样保留**；
- 新增选项默认值：阅读时长/版权/外链新窗口/关闭评论/表情/嵌入 均为关闭，自定义 CSS 与登录页图片为空，每页文章数与摘要字数为 `0`（跟随原行为）；
- 若此前开启了「CDN」选项，现在 `style.css` 固定本地加载，不会出现新旧样式混用。

### 注意
- 本定制版已移除自动更新检查（避免上游更新覆盖修改）。需要自动更新时请自行集成并指向自己的发布源。
- 已删除主题内不再使用的 `plugin-update-checker/` 与 `gutenberg-src/` 目录（后者仅为区块编辑器编译源码，成品 `blocks/blocks.build.js` 保留）。

---

## 五、验证情况

测试环境：Docker + `wordpress:latest`（WordPress 7.1.3）+ MariaDB 10.11 + `php:8.3-cli`（8.3.35），`WP_DEBUG = true`，错误写入 `debug.log`。

- **前台**：首页、文章页、页面、分类、标签、作者、搜索、404、RSS/评论 Feed、REST API 全部正常响应；
- **后台**：主题「样式/功能/关于」三页、文章编辑器均正常加载；两张设置表单通过真实表单提交验证保存成功；
- **新选项逐项验证**：每页文章数（分页变化）、首页排除分类（对应文章消失）、摘要字数（40 → 40 字符）、阅读时长（文章页出现、页面不出现）、CC 版权与自定义版权、外链 `target`（站外添加、站内不添加）、关闭评论（评论表单/列表消失）、自定义 CSS 注入、禁用 emoji（`wp-emoji` 脚本 8 处 → 0 处）、禁用 oEmbed、登录页背景/Logo（CSS 输出正确）；
- **日志**：以上全部操作后 `debug.log` **无任何 Notice / Warning / Deprecated / Fatal**；
- **打包验证**：在全新 WordPress 7.1.3 实例上通过「上传主题 zip → 激活」安装成功，前台/后台/表单保存均正常、日志为空。

### 已知限制
- 「目录层级（TOC depth）」未加入：`js/toc.js` 是编译产物，改层级需要重新执行 webpack 构建；
- 主题各模板自行输出 `<body>`（未统一使用 `body_class()`/`wp_body_open()`），本次未改动页面结构，避免影响现有样式，因此未接入 `wp_body_open`；
- 主题的区块编辑器 JS 仍依赖 `wp-editor` 句柄（WP 7.1 仍提供），后续 WP 移除该句柄时需重新编译 `blocks/blocks.build.js`。

---

## 六、主要文件变更

```
M  functions.php               选项读写、初始化、兼容性修复、新增钩子
M  includes/default_value.php  新增 13 个默认值；安全翻译
M  includes/admin_init_fn.php  初始化改为合并写入
M  includes/admin_init_ver.php 版本号 2.1.0
A  includes/mdx-extra.php      新增：13 个选项的前端/登录页实现
M  admin_fn.php                新增「阅读增强」选项卡与保存逻辑
M  admin_style.php             新增「外观增强」选项卡与保存逻辑
M  admin_functions.php         重写「关于」页与初始化，移除远程请求
M  header.php                  去除 BOM
M  style.css                   主题元数据（版本/兼容性/许可证）
M  package.json                版本 2.1.0
D  plugin-update-checker/      移除失效的自动更新库
D  gutenberg-src/              移除区块编辑器编译源码（成品保留）
```

---

## 七、许可

原主题版权归 Axton Yao 所有，遵循 **GNU General Public License v3.0**。本定制版同样以 GPLv3 发布，保留原作者署名与 `LICENSE` 文件。
