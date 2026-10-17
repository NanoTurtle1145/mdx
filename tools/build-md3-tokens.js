#!/usr/bin/env node
/**
 * 生成 MD3 设计令牌样式表：css/md3-tokens.css
 *
 * 配色使用 Material Color Utilities（与 mdui 2 / Material You 相同的算法），
 * 根据主题的 20 种主色、17 种强调色分别生成浅色 / 深色两套调色板。
 *
 * 使用方法：
 *   npm install --no-save @material/material-color-utilities@0.2.7
 *   node tools/build-md3-tokens.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const {
  CorePalette,
  Scheme,
  argbFromHex,
  hexFromArgb,
} = require('@material/material-color-utilities');

// 与主题后台「主题颜色 / 强调色」选择器保持一致
const PRIMARY = {
  red: '#f44336',
  pink: '#e91e63',
  purple: '#9c27b0',
  'deep-purple': '#673ab7',
  indigo: '#3f51b5',
  blue: '#2196f3',
  'light-blue': '#03a9f4',
  cyan: '#00bcd4',
  teal: '#009688',
  green: '#4caf50',
  'light-green': '#8bc34a',
  lime: '#cddc39',
  yellow: '#ffeb3b',
  amber: '#ffc107',
  orange: '#ff9800',
  'deep-orange': '#ff5722',
  brown: '#795548',
  grey: '#9e9e9e',
  'blue-grey': '#607d8b',
  white: '#9e9e9e',
};

const ACCENT = {
  red: '#ff5252',
  pink: '#ff4081',
  purple: '#e040fb',
  'deep-purple': '#7c4dff',
  indigo: '#536dfe',
  blue: '#448aff',
  'light-blue': '#40c4ff',
  cyan: '#18ffff',
  teal: '#64ffda',
  green: '#69f0ae',
  'light-green': '#b2ff59',
  lime: '#eeff41',
  yellow: '#ffff00',
  amber: '#ffd740',
  orange: '#ffab40',
  'deep-orange': '#ff6e40',
};

function rgb(hex) {
  const v = hexFromArgb(argbFromHex(hex));
  const n = parseInt(v.slice(1), 16);
  return `${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}`;
}

function tone(palette, t) {
  return rgb(hexFromArgb(palette.tone(t)));
}

/** 由主色生成一套完整的 MD3 颜色语义（含 surface / secondary 等）。 */
function primaryScheme(seed) {
  const core = CorePalette.of(argbFromHex(seed));
  const light = Scheme.light(argbFromHex(seed));
  const dark = Scheme.dark(argbFromHex(seed));
  const n1 = core.n1;
  const n2 = core.n2;

  return {
    light: {
      'primary': rgb(hexFromArgb(light.primary)),
      'on-primary': rgb(hexFromArgb(light.onPrimary)),
      'primary-container': rgb(hexFromArgb(light.primaryContainer)),
      'on-primary-container': rgb(hexFromArgb(light.onPrimaryContainer)),
      'inverse-primary': rgb(hexFromArgb(light.inversePrimary)),
      'secondary': rgb(hexFromArgb(light.secondary)),
      'on-secondary': rgb(hexFromArgb(light.onSecondary)),
      'secondary-container': rgb(hexFromArgb(light.secondaryContainer)),
      'on-secondary-container': rgb(hexFromArgb(light.onSecondaryContainer)),
      'surface': tone(n1, 98),
      'on-surface': tone(n1, 10),
      'surface-dim': tone(n1, 87),
      'surface-bright': tone(n1, 98),
      'surface-container-lowest': tone(n1, 100),
      'surface-container-low': tone(n1, 96),
      'surface-container': tone(n1, 94),
      'surface-container-high': tone(n1, 92),
      'surface-container-highest': tone(n1, 90),
      'surface-variant': tone(n2, 90),
      'on-surface-variant': tone(n2, 30),
      'background': tone(n1, 98),
      'on-background': tone(n1, 10),
      'inverse-surface': tone(n1, 20),
      'inverse-on-surface': tone(n1, 95),
      'outline': tone(n2, 50),
      'outline-variant': tone(n2, 80),
      'surface-tint': rgb(hexFromArgb(light.primary)),
    },
    dark: {
      'primary': rgb(hexFromArgb(dark.primary)),
      'on-primary': rgb(hexFromArgb(dark.onPrimary)),
      'primary-container': rgb(hexFromArgb(dark.primaryContainer)),
      'on-primary-container': rgb(hexFromArgb(dark.onPrimaryContainer)),
      'inverse-primary': rgb(hexFromArgb(dark.inversePrimary)),
      'secondary': rgb(hexFromArgb(dark.secondary)),
      'on-secondary': rgb(hexFromArgb(dark.onSecondary)),
      'secondary-container': rgb(hexFromArgb(dark.secondaryContainer)),
      'on-secondary-container': rgb(hexFromArgb(dark.onSecondaryContainer)),
      'surface': tone(n1, 6),
      'on-surface': tone(n1, 90),
      'surface-dim': tone(n1, 6),
      'surface-bright': tone(n1, 24),
      'surface-container-lowest': tone(n1, 4),
      'surface-container-low': tone(n1, 10),
      'surface-container': tone(n1, 12),
      'surface-container-high': tone(n1, 17),
      'surface-container-highest': tone(n1, 22),
      'surface-variant': tone(n2, 30),
      'on-surface-variant': tone(n2, 80),
      'background': tone(n1, 6),
      'on-background': tone(n1, 90),
      'inverse-surface': tone(n1, 90),
      'inverse-on-surface': tone(n1, 20),
      'outline': tone(n2, 60),
      'outline-variant': tone(n2, 30),
      'surface-tint': rgb(hexFromArgb(dark.primary)),
    },
  };
}

/** 由强调色生成 tertiary（第三色）语义。 */
function tertiaryScheme(seed) {
  const core = CorePalette.of(argbFromHex(seed));
  const p = core.a1;
  return {
    light: {
      'tertiary': tone(p, 40),
      'on-tertiary': tone(p, 100),
      'tertiary-container': tone(p, 90),
      'on-tertiary-container': tone(p, 10),
    },
    dark: {
      'tertiary': tone(p, 80),
      'on-tertiary': tone(p, 20),
      'tertiary-container': tone(p, 30),
      'on-tertiary-container': tone(p, 90),
    },
  };
}

function block(selector, vars) {
  const lines = Object.keys(vars).map((k) => `  --mdui-color-${k}: ${vars[k]};`);
  return `${selector} {\n${lines.join('\n')}\n}\n`;
}

const out = [];
out.push('/*!');
out.push(' * MD3 设计令牌（颜色部分）—— 由 tools/build-md3-tokens.js 自动生成，请勿手动编辑。');
out.push(' * 算法：Material Color Utilities（与 mdui 2 / Material You 一致）。');
out.push(' * 每个主题主色 / 强调色都会生成浅色与深色两套语义颜色。');
out.push(' */');
out.push('');

// 无 body class 时的兜底（indigo + pink）
const fallbackPrimary = primaryScheme(PRIMARY.indigo);
const fallbackAccent = tertiaryScheme(ACCENT.pink);
out.push(':root {');
out.push('  --mdui-color-error: 179, 38, 30;');
out.push('  --mdui-color-on-error: 255, 255, 255;');
out.push('  --mdui-color-error-container: 249, 222, 220;');
out.push('  --mdui-color-on-error-container: 65, 14, 11;');
out.push('  --mdui-color-shadow: 0, 0, 0;');
out.push('  --mdui-color-scrim: 0, 0, 0;');
out.push('}');
out.push('');
out.push(block('body', fallbackPrimary.light));
out.push(block('body.mdui-theme-layout-dark', fallbackPrimary.dark));
out.push(block('body', fallbackAccent.light));
out.push(block('body.mdui-theme-layout-dark', fallbackAccent.dark));
out.push('');

// 每种主色
for (const name of Object.keys(PRIMARY)) {
  const s = primaryScheme(PRIMARY[name]);
  out.push(`/* 主色：${name} */`);
  out.push(block(`body.mdui-theme-primary-${name}`, s.light));
  out.push(block(`body.mdui-theme-primary-${name}.mdui-theme-layout-dark`, s.dark));
}

// 每种强调色 → tertiary
for (const name of Object.keys(ACCENT)) {
  const s = tertiaryScheme(ACCENT[name]);
  out.push(`/* 强调色：${name}（映射到 MD3 tertiary） */`);
  out.push(block(`body.mdui-theme-accent-${name}`, s.light));
  out.push(block(`body.mdui-theme-accent-${name}.mdui-theme-layout-dark`, s.dark));
}

// 「白色」主题：纯白表面
const whiteLead = primaryScheme(PRIMARY.grey);
for (const mode of ['light', 'dark']) {
  const vars = whiteLead[mode];
  const override = mode === 'light'
    ? {
        'surface': '255, 255, 255',
        'surface-bright': '255, 255, 255',
        'surface-container-lowest': '255, 255, 255',
        'surface-container-low': '252, 252, 252',
        'surface-container': '248, 248, 248',
        'surface-container-high': '244, 244, 244',
        'surface-container-highest': '238, 238, 238',
        'background': '255, 255, 255',
        'surface-variant': '236, 236, 236',
      }
    : {
        'surface': '18, 18, 18',
        'surface-container-lowest': '0, 0, 0',
        'surface-container-low': '12, 12, 12',
        'surface-container': '18, 18, 18',
        'surface-container-high': '24, 24, 24',
        'surface-container-highest': '30, 30, 30',
        'background': '18, 18, 18',
      };
  const merged = Object.assign({}, vars, override);
  const sel = mode === 'light'
    ? 'body.mdx-theme-white'
    : 'body.mdx-theme-white.mdui-theme-layout-dark';
  out.push(`/* 白色主题：${mode} */`);
  out.push(block(sel, merged));
}

const css = out.join('\n') + '\n';
const target = path.join(__dirname, '..', 'css', 'md3-tokens.css');
fs.writeFileSync(target, css, 'utf8');
console.log(`written ${target} (${(css.length / 1024).toFixed(1)} KB)`);
