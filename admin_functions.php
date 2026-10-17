<?php
/**
 * MDx 后台菜单与主题信息页
 *
 * 说明（兼容性修改）：
 *  - 移除了首次激活时向后端发送统计请求的逻辑；
 *  - 移除了“关于”页面对 mdxupdate.flyhigher.top / jsdelivr 的阻塞式远程请求，
 *    这些请求在服务器无法访问外网时会让后台页面卡住数十秒；
 *  - 移除了写死主题目录名为 mdx 的版权文件检查，避免主题目录改名后报错。
 */

function mdx_admin_function() {
    add_menu_page(__('MDx 主题', 'mdx'), __('MDx 主题', 'mdx'), 'manage_options', 'mdx_admin', 'mdx_display_sub_function', 'dashicons-admin-customizer');
}
function mdx_add_admin() {
    add_submenu_page('mdx_admin', __('MDx 主题 - 样式', 'mdx'), __('样式', 'mdx'), 'manage_options', 'mdx_styles', 'mdx_display_sub_function_one');
    add_submenu_page('mdx_admin', __('MDx 主题 - 功能', 'mdx'), __('功能', 'mdx'), 'manage_options', 'mdx_functions', 'mdx_display_sub_function_two');
    add_submenu_page('mdx_admin', __('MDx 主题 - 关于', 'mdx'), __('关于', 'mdx'), 'manage_options', 'mdx_about', 'mdx_display_sub_function_three');
}
function mdx_display_sub_function() {
    echo '<h1>' . __('MDx 主题', 'mdx') . '</h1>';
}
function mdx_display_sub_function_one() {
    wp_register_style('mdx_admin_preview', get_template_directory_uri() . '/includes/admin_preview.css');
    wp_enqueue_style('mdx_admin_preview');
    require_once __DIR__ . '/admin_style.php';
}
function mdx_display_sub_function_two() {
    wp_register_style('mdx_admin_functions', get_template_directory_uri() . '/includes/admin_functions.css');
    wp_enqueue_style('mdx_admin_functions');
    require_once __DIR__ . '/admin_fn.php';
}
function mdx_display_sub_function_three() {
    wp_register_style('mdx_admin', get_template_directory_uri() . '/includes/admin.css');
    wp_enqueue_style('mdx_admin');

    $mdx_theme = wp_get_theme();
    $mdx_version = get_option('mdx_version');
    if (empty($mdx_version)) {
        $mdx_version = $mdx_theme->get('Version');
    }
    $mdx_version_commit = get_option('mdx_version_commit');

    echo '<div class="wrap">
<h1>' . __('MDx 主题 - 关于', 'mdx') . '</h1>
<p class="mdx-admin-img"><img src="' . esc_url(get_template_directory_uri() . '/img/admin.jpg') . '"></p>
<h2 style="font-size:19px;">' . __('感谢使用 MDx 主题', 'mdx') . '</h2>
<p style="font-size:15px;">' . __('我是 Axton Yao，这个主题由我开发。我的网站是', 'mdx') . ' <a href="https://flyhigher.top" target="_blank">flyhigher.top</a>' . __('。', 'mdx') . '</p>
<p style="font-size:15px;">' . __('对主题有任何疑问，建议先查阅 ', 'mdx') . '<a href="https://doc.flyhigher.top/mdx/" target="_blank">' . __('主题文档', 'mdx') . '</a>' . __('。', 'mdx') . '</p>
<p style="font-size:15px;">' . __('这个项目的 Github 地址是 ', 'mdx') . '<a href="https://github.com/yrccondor/mdx" target="_blank">github.com/yrccondor/mdx</a>' . __('。如果你有兴趣，欢迎为这个项目做出贡献。同时，求 Star。', 'mdx') . '</p>
<p style="font-size:15px;">' . __('这个主题的诞生离不开 MDUI，这是一个优秀的前端框架项目，你可以在他们的官方网站上了解更多：', 'mdx') . '<a href="https://mdui.org" target="_blank">mdui.org</a>' . __('。', 'mdx') . '</p>
<br>
<p style="font-size:17px;"><strong>' . __('感谢以下译者帮助翻译 MDx：', 'mdx') . '</strong></p>
<ul style="font-size:15px;margin-left:17px;list-style:disc">
<li><a href="https://github.com/Sn0bzy" target="_blank">Sn0bzy</a> (Türkçe)</li>
<li><a href="https://github.com/yechs" target="_blank">yechs</a> (English)</li>
<li><a href="https://github.com/AngelKitty" target="_blank">AngelKitty</a> (繁體中文)</li>
</ul>
<br>
<p style="font-size:12px;">' . __('当前版本 v', 'mdx') . esc_html($mdx_version_commit ? $mdx_version_commit : $mdx_version) . '</p>
<p style="font-size:12px;">' . __('兼容 WordPress', 'mdx') . ' ' . esc_html(get_bloginfo('version')) . '（推荐 WordPress 6.4 及以上版本）</p>
<p style="font-size:12px;">' . __('本主题为兼容新版 WordPress 的定制版本，已内置更新到 2.1.0 的增强选项。', 'mdx') . '</p>
<br>
<p style="font-size:17px;"><strong>' . __('这款主题献给 Demi Zhou', 'mdx') . '</strong></p>
</div>';
}
function remove_submenu() {
    remove_submenu_page('mdx_admin', 'mdx_admin');
}
add_action('admin_menu', 'mdx_admin_function');
add_action('admin_menu', 'mdx_add_admin');
add_action('admin_menu', 'remove_submenu');

//初始化主题设置，只有第一次激活主题时调用
function mdx_init_theme() {
    if (!get_option('mdx_first_init')) {
        include_once __DIR__ . '/includes/admin_init_fn.php';
        update_option('mdx_first_init', '1');
    }
    add_action('admin_notices', 'mdx_custom_admin_notice');
}
add_action('after_switch_theme', 'mdx_init_theme');

function mdx_custom_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('MDx 主题现已激活，你可以前往<a href="admin.php?page=mdx_styles">主题设置页面</a>对主题进行个性化定制。', 'mdx'); ?></p>
    </div>
    <?php
}
function mdx_php_error() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('你似乎没有启用 <code>mbstring</code> PHP 拓展，这会导致 MDx 的部分功能无法正常运行（例如摘要截取）。建议安装并启用 mbstring 拓展。', 'mdx'); ?></p>
    </div>
    <?php
}
if (!function_exists("mb_strimwidth")) {
    add_action('admin_notices', 'mdx_php_error');
}
?>
