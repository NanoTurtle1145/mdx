<?php
/**
 * MDx 增强选项运行逻辑
 *
 * 本文件由 functions.php 载入，负责新增自定义选项在前端/登录页的实际行为。
 * 兼容 WordPress 6.4 ~ 7.1 / PHP 7.4 ~ 8.3。
 *
 * 新增选项一览：
 *  - mdx_posts_per_page        每页文章数
 *  - mdx_home_exclude_cats     首页排除的分类 ID
 *  - mdx_excerpt_length        文章列表摘要字数
 *  - mdx_reading_meta          文章页显示字数与预计阅读时长
 *  - mdx_license               文章版权协议
 *  - mdx_license_custom        自定义版权文本
 *  - mdx_external_link_blank   外链在新窗口打开
 *  - mdx_close_comments        关闭全站评论
 *  - mdx_custom_css            自定义 CSS
 *  - mdx_disable_emoji         禁用 WordPress 表情符号脚本
 *  - mdx_disable_embeds        禁用 oEmbed 嵌入脚本
 *  - mdx_login_bg              登录页背景图
 *  - mdx_login_logo            登录页 Logo
 */

/* ==========================================================================
 * 工具函数
 * ========================================================================== */

/**
 * 判断某个布尔型选项是否开启。
 *
 * @param string $name 选项名。
 * @return bool
 */
function mdx_option_true($name) {
    return mdx_get_option($name) === 'true';
}

/**
 * 解析逗号分隔的 ID 列表（支持中英文逗号、空格）。
 *
 * @param string $value 原始值。
 * @return int[]
 */
function mdx_parse_id_list($value) {
    if (!is_string($value) || $value === '') {
        return array();
    }
    $ids = array_map('absint', preg_split('/[\s,，、]+/u', $value));
    $ids = array_filter($ids);
    return array_values(array_unique($ids));
}

/**
 * 清理自定义 CSS，避免提前闭合 <style> 标签或注入 PHP。
 *
 * @param string $css 原始 CSS。
 * @return string
 */
function mdx_sanitize_css($css) {
    if (!is_string($css)) {
        return '';
    }
    // 禁止出现任何 HTML / PHP 标签
    $css = preg_replace('#<\s*/?\s*(style|script)[^>]*>#i', '', $css);
    $css = str_ireplace(array('<?php', '<?=', '<?', '?>', '<!--', '-->'), '', $css);
    return $css;
}

/* ==========================================================================
 * 1. 每页文章数 & 首页排除分类
 * ========================================================================== */
function mdx_extra_pre_get_posts($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    $per_page = (int) mdx_get_option('mdx_posts_per_page');
    if ($per_page > 0 && !$query->is_feed() && ($query->is_home() || $query->is_archive() || $query->is_search())) {
        $query->set('posts_per_page', $per_page);
    }

    if ($query->is_home()) {
        $exclude = mdx_parse_id_list(mdx_get_option('mdx_home_exclude_cats'));
        if (!empty($exclude)) {
            $query->set('category__not_in', $exclude);
        }
    }
}
add_action('pre_get_posts', 'mdx_extra_pre_get_posts');

/* ==========================================================================
 * 2. 文章页：字数 / 预计阅读时长 / 版权协议
 * ========================================================================== */

/**
 * 计算文章字数与预计阅读时长。
 *
 * 中文按 300 字/分钟、英文按 200 词/分钟估算。
 *
 * @param WP_Post $post 文章对象。
 * @return array{chars:int, minutes:int}
 */
function mdx_get_reading_data($post) {
    $content = strip_shortcodes($post->post_content);
    $content = wp_strip_all_tags($content);
    $content = preg_replace('/\s+/u', ' ', $content);

    $cjk   = preg_match_all('/[\x{3000}-\x{303f}\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', $content, $matches);
    $latin = preg_match_all('/[A-Za-z0-9]+/u', $content, $matches2);

    $chars   = (int) $cjk + (int) $latin;
    $minutes = (int) ceil(((int) $cjk) / 300 + ((int) $latin) / 200);
    if ($minutes < 1 && $chars > 0) {
        $minutes = 1;
    }

    return array('chars' => $chars, 'minutes' => $minutes);
}

/**
 * 生成版权协议 HTML。
 *
 * @return string
 */
function mdx_get_license_html() {
    $type = (string) mdx_get_option('mdx_license');
    if ($type === '' || $type === 'none') {
        return '';
    }

    if ($type === 'custom') {
        $custom = (string) mdx_get_option('mdx_license_custom');
        if (trim($custom) === '') {
            return '';
        }
        return '<div class="mdx-license mdx-license-custom">' . wp_kses_post(wpautop($custom)) . '</div>';
    }

    $licenses = array(
        'cc-by'        => array('CC BY 4.0', 'https://creativecommons.org/licenses/by/4.0/', '知识共享 署名 4.0'),
        'cc-by-sa'     => array('CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/', '知识共享 署名-相同方式共享 4.0'),
        'cc-by-nd'     => array('CC BY-ND 4.0', 'https://creativecommons.org/licenses/by-nd/4.0/', '知识共享 署名-禁止演绎 4.0'),
        'cc-by-nc'     => array('CC BY-NC 4.0', 'https://creativecommons.org/licenses/by-nc/4.0/', '知识共享 署名-非商业性使用 4.0'),
        'cc-by-nc-sa'  => array('CC BY-NC-SA 4.0', 'https://creativecommons.org/licenses/by-nc-sa/4.0/', '知识共享 署名-非商业性使用-相同方式共享 4.0'),
        'cc-by-nc-nd'  => array('CC BY-NC-ND 4.0', 'https://creativecommons.org/licenses/by-nc-nd/4.0/', '知识共享 署名-非商业性使用-禁止演绎 4.0'),
        'cc0'          => array('CC0 1.0', 'https://creativecommons.org/publicdomain/zero/1.0/', '公共领域贡献'),
    );

    if (!isset($licenses[$type])) {
        return '';
    }

    $license = $licenses[$type];
    $post    = get_post();
    if (!$post) {
        return '';
    }

    $author = get_the_author_meta('display_name', $post->post_author);
    if (empty($author)) {
        $author = get_bloginfo('name');
    }

    $license_link = '<a href="' . esc_url($license[1]) . '" title="' . esc_attr($license[2]) . '" rel="license noopener noreferrer" target="_blank">' . esc_html($license[0]) . '</a>';

    $items   = array();
    $items[] = sprintf(__('本文作者：%s', 'mdx'), esc_html($author));
    $items[] = sprintf(__('版权协议：%s', 'mdx'), $license_link);
    $items[] = sprintf(__('原文链接：%s', 'mdx'), '<a href="' . esc_url(get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a>');

    return '<div class="mdx-license mdx-license-' . esc_attr($type) . '">' . implode('<span class="mdx-license-sep"> · </span>', $items) . '</div>';
}

/**
 * 在文章正文末尾追加阅读信息与版权协议。
 *
 * @param string $content 文章内容。
 * @return string
 */
function mdx_extra_append_post_meta($content) {
    if (is_admin() || is_feed() || !is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    static $appended = false;
    if ($appended) {
        return $content;
    }
    $appended = true;

    $post = get_post();
    if (!$post) {
        return $content;
    }

    $extra = '';

    if (mdx_option_true('mdx_reading_meta')) {
        $reading = mdx_get_reading_data($post);
        if ($reading['chars'] > 0) {
            $extra .= '<div class="mdx-reading-meta">'
                . '<i class="mdui-icon material-icons">&#xe8b5;</i> '
                . sprintf(
                    __('全文约 %1$s 字 · 预计阅读 %2$s 分钟', 'mdx'),
                    number_format_i18n($reading['chars']),
                    number_format_i18n(max(1, $reading['minutes']))
                )
                . '</div>';
        }
    }

    $extra .= mdx_get_license_html();

    return $content . $extra;
}
add_filter('the_content', 'mdx_extra_append_post_meta', 21);

/* ==========================================================================
 * 3. 外链在新窗口打开
 * ========================================================================== */
function mdx_extra_external_links($content) {
    if (is_admin() || is_feed() || !mdx_option_true('mdx_external_link_blank')) {
        return $content;
    }

    $home_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    if (empty($home_host)) {
        return $content;
    }
    $home_host = preg_replace('/^www\./i', '', $home_host);

    return preg_replace_callback('#<a\s([^>]+)>#i', function ($matches) use ($home_host) {
        $attrs = $matches[1];

        if (!preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $attrs, $href_match)) {
            return $matches[0];
        }

        $href = trim($href_match[2]);
        if (!preg_match('#^https?://#i', $href)) {
            return $matches[0]; // 相对链接、锚点、mailto、tel 等保持原样
        }

        $host = wp_parse_url($href, PHP_URL_HOST);
        if (empty($host)) {
            return $matches[0];
        }
        $host = preg_replace('/^www\./i', '', $host);
        if (strcasecmp($host, $home_host) === 0) {
            return $matches[0]; // 站内链接保持原样
        }

        // 已有 target 属性则不重复添加
        if (!preg_match('/\starget\s*=/i', $attrs)) {
            $attrs .= ' target="_blank"';
        }

        // 补全 rel
        if (preg_match('/\srel\s*=\s*(["\'])(.*?)\1/i', $attrs, $rel_match)) {
            $rel  = $rel_match[2];
            $need = array();
            if (stripos($rel, 'noopener') === false) {
                $need[] = 'noopener';
            }
            if (stripos($rel, 'noreferrer') === false) {
                $need[] = 'noreferrer';
            }
            if (!empty($need)) {
                $attrs = str_replace($rel_match[0], ' rel="' . trim($rel . ' ' . implode(' ', $need)) . '"', $attrs);
            }
        } else {
            $attrs .= ' rel="noopener noreferrer"';
        }

        return '<a ' . $attrs . '>';
    }, $content);
}
add_filter('the_content', 'mdx_extra_external_links', 22);

/* ==========================================================================
 * 4. 关闭全站评论
 * ========================================================================== */
if (mdx_option_true('mdx_close_comments')) {
    add_filter('comments_open', '__return_false', 20);
    add_filter('pings_open', '__return_false', 20);
    add_filter('comments_array', function () {
        return array();
    }, 20);
    add_filter('get_comments_number', function () {
        return 0;
    }, 20);
}

/* ==========================================================================
 * 5. 自定义 CSS 与新增元素的默认样式
 * ========================================================================== */
function mdx_extra_inline_css() {
    if (!wp_style_is('mdx_style_css', 'enqueued')) {
        return;
    }

    $css = '
/* MDx 增强选项默认样式 */
.mdx-reading-meta{margin:30px 0 0;font-size:14px;line-height:1.6;color:rgba(0,0,0,.54);}
.mdx-license{margin:18px 0 0;padding:14px 18px;border-radius:4px;background:rgba(0,0,0,.04);font-size:13px;line-height:1.8;color:rgba(0,0,0,.6);word-break:break-all;}
.mdx-license a{color:inherit;text-decoration:underline;}
.mdx-license-sep{opacity:.45;}
.mdui-theme-layout-dark .mdx-reading-meta{color:hsla(0,0%,100%,.54);}
.mdui-theme-layout-dark .mdx-license{background:hsla(0,0%,100%,.07);color:hsla(0,0%,100%,.6);}
';

    $custom_css = mdx_sanitize_css(mdx_get_option('mdx_custom_css'));
    if (trim($custom_css) !== '') {
        $css .= "\n/* 自定义 CSS */\n" . $custom_css . "\n";
    }

    wp_add_inline_style('mdx_style_css', $css);
}
add_action('wp_enqueue_scripts', 'mdx_extra_inline_css', 30);

/* ==========================================================================
 * 6. 禁用 WordPress 表情符号脚本（性能优化选项）
 * ========================================================================== */
function mdx_extra_disable_emoji() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content', 'wp_staticize_emoji');
    remove_filter('comment_text', 'wp_staticize_emoji');
    remove_filter('widget_text', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('emoji_svg_url', '__return_false');
    add_filter('tiny_mce_plugins', function ($plugins) {
        return is_array($plugins) ? array_diff($plugins, array('wpemoji')) : $plugins;
    });
}
if (mdx_option_true('mdx_disable_emoji')) {
    add_action('init', 'mdx_extra_disable_emoji');
}

/* ==========================================================================
 * 7. 禁用 oEmbed 嵌入脚本（性能优化选项）
 * ========================================================================== */
if (mdx_option_true('mdx_disable_embeds')) {
    add_action('init', function () {
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        add_filter('embed_oembed_discover', '__return_false');
        remove_filter('the_content', 'wpembed_filter_fetch');
        add_filter('tiny_mce_plugins', function ($plugins) {
            return is_array($plugins) ? array_diff($plugins, array('wpembed')) : $plugins;
        });
    });
}

/* ==========================================================================
 * 8. 登录页背景图与 Logo
 * ========================================================================== */
function mdx_extra_login_style() {
    $bg   = (string) mdx_get_option('mdx_login_bg');
    $logo = (string) mdx_get_option('mdx_login_logo');
    if ($bg === '' && $logo === '') {
        return;
    }

    echo '<style type="text/css">';
    if ($bg !== '') {
        echo 'body.login{background-image:url("' . esc_url_raw($bg) . '");background-size:cover;background-position:center center;background-repeat:no-repeat;background-attachment:fixed;}';
        echo 'body.login::before{content:"";position:fixed;top:0;right:0;bottom:0;left:0;background:rgba(0,0,0,.28);z-index:-1;}';
    }
    if ($logo !== '') {
        echo '.login h1 a{background-image:url("' . esc_url_raw($logo) . '");background-size:contain;background-position:center center;background-repeat:no-repeat;width:100%;height:84px;}';
    }
    echo '</style>';
}
add_action('login_head', 'mdx_extra_login_style');

if ((string) mdx_get_option('mdx_login_logo') !== '') {
    add_filter('login_headerurl', function () {
        return home_url('/');
    });
    add_filter('login_headertext', function () {
        return get_bloginfo('name');
    });
}

/* ==========================================================================
 * 9. MD3（Material Design 3）外观层
 *    - 设计令牌与组件样式位于 css/md3-tokens.css（构建生成）与 css/md3.css
 *    - 这里负责：<html> 上的类名、OLED / 无色调表面等运行时变量覆盖
 * ========================================================================== */

/**
 * 是否启用 MD3 外观。
 *
 * @return bool
 */
function mdx_md3_enabled() {
    return mdx_option_true('mdx_md3');
}

/**
 * 在 <html> 标签上追加 MD3 相关的类名。
 *
 * @param string $output language_attributes() 的原始输出。
 * @return string
 */
function mdx_md3_html_class($output) {
    if (!mdx_md3_enabled()) {
        return $output;
    }

    $classes = array('mdx-md3');

    $round = (string) mdx_get_option('mdx_md3_round');
    if ($round === 'small' || $round === 'large') {
        $classes[] = 'mdx-md3-round-' . $round;
    }

    if ((string) mdx_get_option('mdx_md3_reading_font') === 'serif') {
        $classes[] = 'mdx-md3-reading-serif';
    }

    if (!mdx_option_true('mdx_md3_tonal')) {
        $classes[] = 'mdx-md3-flat';
    }

    return $output . ' class="' . implode(' ', $classes) . '"';
}
add_filter('language_attributes', 'mdx_md3_html_class');

/**
 * 输出 MD3 的运行时变量覆盖（OLED 纯黑表面、无色调表面等）。
 *
 * @param string $handle 样式句柄。
 * @return void
 */
function mdx_md3_inline_css($handle = 'mdx_md3_css') {
    if (!mdx_md3_enabled()) {
        return;
    }

    $css = '';

    // OLED：深色模式下把表面压到纯黑附近
    if ((string) mdx_get_option('mdx_styles_dark') === 'oled' || (string) mdx_get_option('mdx_night_style') === 'oled') {
        $css .= 'html.mdx-md3 body.mdui-theme-layout-dark{'
            . '--mdui-color-surface:0,0,0;'
            . '--mdui-color-background:0,0,0;'
            . '--mdui-color-surface-dim:0,0,0;'
            . '--mdui-color-surface-container-lowest:0,0,0;'
            . '--mdui-color-surface-container-low:6,6,6;'
            . '--mdui-color-surface-container:10,10,10;'
            . '--mdui-color-surface-container-high:14,14,14;'
            . '--mdui-color-surface-container-highest:18,18,18;}';
    }

    // 关闭「色调表面」：MD3 的表面色不带主色色调，回到中性灰白
    if (!mdx_option_true('mdx_md3_tonal')) {
        $css .= 'html.mdx-md3 body{'
            . '--mdui-color-surface:250,250,250;'
            . '--mdui-color-background:250,250,250;'
            . '--mdui-color-surface-bright:255,255,255;'
            . '--mdui-color-surface-container-lowest:255,255,255;'
            . '--mdui-color-surface-container-low:255,255,255;'
            . '--mdui-color-surface-container:245,245,245;'
            . '--mdui-color-surface-container-high:238,238,238;'
            . '--mdui-color-surface-container-highest:232,232,232;'
            . '--mdui-color-surface-variant:236,236,236;}'
            . 'html.mdx-md3 body.mdui-theme-layout-dark{'
            . '--mdui-color-surface:18,18,18;'
            . '--mdui-color-background:18,18,18;'
            . '--mdui-color-surface-bright:30,30,30;'
            . '--mdui-color-surface-container-lowest:0,0,0;'
            . '--mdui-color-surface-container-low:24,24,24;'
            . '--mdui-color-surface-container:30,30,30;'
            . '--mdui-color-surface-container-high:36,36,36;'
            . '--mdui-color-surface-container-highest:42,42,42;'
            . '--mdui-color-surface-variant:44,44,44;}';
    }

    if ($css !== '') {
        wp_add_inline_style($handle, $css);
    }
}
