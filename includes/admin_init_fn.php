<?php
// 首次激活主题时写入默认选项。
// 注意：必须与已有选项合并，不能直接覆盖，否则重复初始化会冲掉用户已保存的设置。
require_once __DIR__ . '/admin_init_ver.php';
require __DIR__ . '/default_value.php';

$mdx_subdir_flag_init = 'normal';
if (stripos(explode('//', home_url())[1], '/')) {
    $mdx_subdir_flag_init = 'sub';
}
$mdx_default_values['mdx_install'] = $mdx_subdir_flag_init;

$mdx_existing_options = get_option('mdx_all_options');
if (!is_array($mdx_existing_options)) {
    $mdx_existing_options = array();
}
update_option('mdx_all_options', array_merge($mdx_default_values, $mdx_existing_options));
?>
