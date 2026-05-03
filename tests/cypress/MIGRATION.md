# Cypress.env() migration

Cypress 15.10 deprecated `Cypress.env()` and split the API into two replacements.
We saw the warning during a CI run on 2026-05-02.

**Status:** Deferred until after the v7 Cypress test cleanup. Migrate the survivors
of that cleanup, then delete this file.

## Plan

1. Run the Cypress dead-test sweep (drop specs that target deleted v6 surface:
   Voices tab, PlayerColors, IncludeTitle, etc.).
2. Walk the call-site table below; skip any rows in deleted files.
3. Add `expose: { ... }` block to [cypress.config.js](../../cypress.config.js) for
   public values; trim `env: { ... }` to sensitive only.
4. Set `allowCypressEnv: false` in the Cypress config to lock it down.
5. Delete this file.

## API split

| Old | New | Use for |
|---|---|---|
| `Cypress.env('apiKey')` | `cy.env(['apiKey']).then(({ apiKey }) => …)` | sensitive values (async) |
| `Cypress.env('projectId')` | `Cypress.expose('projectId')` | public values (sync) |

`cy.env()` is asynchronous — every inline use (e.g. `.type(Cypress.env('apiKey'))`) becomes a `.then()` continuation. That's the painful part of the migration.

## Config changes

```js
// cypress.config.js
module.exports = defineConfig({
  // ...existing...
  expose: {
    projectId: process.env.BEYONDWORDS_TESTS_PROJECT_ID || '',
    contentId: process.env.BEYONDWORDS_TESTS_CONTENT_ID || '',
    apiUrl:    process.env.BEYONDWORDS_API_URL || '',
  },
  env: {
    wpUsername: 'admin',
    wpPassword: 'password',
    apiKey:     process.env.BEYONDWORDS_TESTS_API_KEY || '',
  },
  allowCypressEnv: false,
});
```

## Call-sites (29 total)

### Sensitive — migrate to `cy.env([...]).then(...)` (10)

#### `apiKey` (8)
- [tests/cypress/support/commands.js:215](support/commands.js#L215) — `.type(Cypress.env('apiKey'))`
- [tests/cypress/e2e/settings/authentication.cy.js:46](e2e/settings/authentication.cy.js#L46) — `.type(Cypress.env('apiKey'))`
- [tests/cypress/e2e/settings/authentication.cy.js:55](e2e/settings/authentication.cy.js#L55) — `.type(Cypress.env('apiKey'))`
- [tests/cypress/e2e/settings/authentication.cy.js:66](e2e/settings/authentication.cy.js#L66) — `.type(Cypress.env('apiKey'))`
- [tests/cypress/e2e/settings/authentication.cy.js:81](e2e/settings/authentication.cy.js#L81) — `Cypress.env('apiKey')` (used in expectation)
- [tests/cypress/e2e/settings/authentication.cy.js:91](e2e/settings/authentication.cy.js#L91) — `Cypress.env('apiKey')` (used in expectation)
- [tests/cypress/e2e/site-health.cy.js:94](e2e/site-health.cy.js#L94) — `Cypress.env('apiKey').slice(-4)`

#### `wpUsername` (1)
- [tests/cypress/support/commands.js:81](support/commands.js#L81) — `const username = Cypress.env('wpUsername')`

#### `wpPassword` (1)
- [tests/cypress/support/commands.js:82](support/commands.js#L82) — `const password = Cypress.env('wpPassword')`

### Public — migrate to `Cypress.expose(...)` (19)

#### `projectId` (12)
- [tests/cypress/support/commands.js:218](support/commands.js#L218) — `.type(Cypress.env('projectId'))`
- [tests/cypress/support/commands.js:700](support/commands.js#L700) — `metaValue: Cypress.env('projectId')`
- [tests/cypress/e2e/settings/authentication.cy.js:38](e2e/settings/authentication.cy.js#L38) — `.type(Cypress.env('projectId'))`
- [tests/cypress/e2e/settings/authentication.cy.js:69](e2e/settings/authentication.cy.js#L69) — `.type(Cypress.env('projectId'))`
- [tests/cypress/e2e/settings/authentication.cy.js:85](e2e/settings/authentication.cy.js#L85) — `Cypress.env('projectId')` (expectation)
- [tests/cypress/e2e/settings/authentication.cy.js:95](e2e/settings/authentication.cy.js#L95) — `Cypress.env('projectId')` (expectation)
- [tests/cypress/e2e/filters.cy.js:60](e2e/filters.cy.js#L60) — `parseInt(Cypress.env('projectId'))`
- [tests/cypress/e2e/plugins/wpgraphql.cy.js:70](e2e/plugins/wpgraphql.cy.js#L70) — `parseInt(Cypress.env('projectId'))`
- [tests/cypress/e2e/site-health.cy.js:109](e2e/site-health.cy.js#L109) — `Cypress.env('projectId')`
- [tests/cypress/e2e/classic-editor/add-post.cy.js:238](e2e/classic-editor/add-post.cy.js#L238) — `${Cypress.env('projectId')}` in URL template
- [tests/cypress/e2e/block-editor/add-post.cy.js:240](e2e/block-editor/add-post.cy.js#L240) — `${Cypress.env('projectId')}` in URL template

#### `contentId` (6)
- [tests/cypress/support/commands.js:705](support/commands.js#L705) — `metaValue: Cypress.env('contentId')`
- [tests/cypress/e2e/filters.cy.js:62](e2e/filters.cy.js#L62) — `expect(contentId).to.equal(Cypress.env('contentId'))`
- [tests/cypress/e2e/plugins/wpgraphql.cy.js:74](e2e/plugins/wpgraphql.cy.js#L74) — `Cypress.env('contentId')` (expectation)
- [tests/cypress/e2e/plugins/wpgraphql.cy.js:78](e2e/plugins/wpgraphql.cy.js#L78) — `Cypress.env('contentId')` (expectation)
- [tests/cypress/e2e/classic-editor/content-id.cy.js:40](e2e/classic-editor/content-id.cy.js#L40) — `Cypress.env('contentId')`
- [tests/cypress/e2e/block-editor/content-id.cy.js:43](e2e/block-editor/content-id.cy.js#L43) — `el.value === Cypress.env('contentId')`

#### `apiUrl` (1)
- [tests/cypress/e2e/site-health.cy.js:39](e2e/site-health.cy.js#L39) — `Cypress.env('apiUrl')`

## Reference

- Migration guide: <https://docs.cypress.io/app/references/migration-guide#Migrating-away-from-Cypressenv>
- Cypress 15.10 release notes
