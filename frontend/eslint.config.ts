import {globalIgnores} from 'eslint/config';
import vueTsEslintConfig from '@vue/eslint-config-typescript';
import pluginVue from 'eslint-plugin-vue';
import pluginVitest from '@vitest/eslint-plugin';
import pluginOxlint from 'eslint-plugin-oxlint';

export default [
  globalIgnores([
    '**/dist/**',
    '**/dist-ssr/**',
    '**/coverage/**',
    'env.d.ts',
    'eslint.config.ts',
  ]),

  ...pluginVue.configs['flat/essential'],
  ...vueTsEslintConfig(),

  {
    name: 'app/project-service',
    languageOptions: {
      parserOptions: {
        projectService: {
          defaultProject: './tsconfig.app.json',
        },
      },
    },
  },

  {
    name: 'app/ts-files',
    files: ['**/*.{ts,mts,tsx}'],
    rules: {
      indent: ['error', 4],
      semi: ['error', 'always'],
      'brace-style': ['error', '1tbs'],
      'object-curly-spacing': ['error', 'always'],
      'arrow-body-style': ['error', 'always'],
      '@typescript-eslint/naming-convention': ['error',
        {selector: 'class', format: ['PascalCase']},
        {selector: 'method', modifiers: ['static'], format: ['PascalCase']},
        {selector: 'method', format: ['camelCase']},
        {selector: 'variable', format: ['camelCase']},
      ],
      'no-console': 'off',
      '@typescript-eslint/no-unused-vars': ['error'],
    },
  },

  {
    name: 'app/vue-files',
    files: ['**/*.vue'],
    rules: {
      indent: 'off',
      semi: ['error', 'always'],
      'brace-style': ['error', '1tbs'],
      'object-curly-spacing': ['error', 'always'],
      'arrow-body-style': ['error', 'always'],
      'vue/script-indent': ['error', 4],
      'vue/html-indent': ['error', 4],
      'vue/multi-word-component-names': 'off',
      'vue/max-attributes-per-line': ['error', {
        singleline: {max: 10},
        multiline: {max: 1},
      }],
      'vue/prop-name-casing': ['error', 'snake_case'],
      '@typescript-eslint/naming-convention': ['error',
        {selector: 'class', format: ['PascalCase']},
        {selector: 'method', modifiers: ['static'], format: ['PascalCase']},
        {selector: 'method', format: ['camelCase']},
        {selector: 'variable', format: ['camelCase']},
      ],
      'no-console': 'off',
      '@typescript-eslint/no-unused-vars': ['error'],
    },
  },

  {
    ...pluginVitest.configs.recommended,
    files: ['src/**/__tests__/*'],
  },

  ...pluginOxlint.buildFromOxlintConfigFile('.oxlintrc.json'),
];
