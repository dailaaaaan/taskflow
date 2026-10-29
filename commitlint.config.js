/**
 * Enforces Conventional Commits (https://www.conventionalcommits.org).
 * Allowed types come from @commitlint/config-conventional:
 * build, chore, ci, docs, feat, fix, perf, refactor, revert, style, test.
 *
 * @type {import('@commitlint/types').UserConfig}
 */
export default {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'header-max-length': [2, 'always', 72],
    },
};
