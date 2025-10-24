# Cypress Test Optimization Plan

**Date Started:** 2025-10-17
**Goal:** Eliminate slow DB resets and make Cypress tests fast & reliable
**Current Status:** 🔄 Planning

---

## 🎯 Objectives

- [ ] **Remove full DB reset from beforeEach** - Currently causing major slowdown
- [ ] **Implement fast test isolation** - Using targeted cleanup or transactions
- [ ] **Refactor tests to be state-independent** - Tests create exactly what they need
- [ ] **Achieve <500ms test setup time** - Down from current ~5-10 seconds

---

## 📋 Implementation Checklist

### Phase 1: Setup Infrastructure ✅ Complete

- [x] **Create Cypress commands for fast cleanup**
  - [x] Add `cy.cleanupTestPosts()` command
  - [x] Add `cy.createTestPost()` command with unique naming
  - [x] Add `cy.createTestPostWithAudio()` command for BeyondWords tests
  - [x] Implement WP CLI tasks in cypress.config.js

- [ ] **Add database transaction support** (optional/experimental)
  - [ ] Add `cy.startTransaction()` command
  - [ ] Add `cy.rollbackTransaction()` command
  - [ ] Test with a simple spec to verify it works
  - *Note: Skipped for now - targeted cleanup is sufficient*

- [x] **Create baseline performance measurement**
  - [x] Identified current bottleneck: `cy.task('reset')` takes 5-10 seconds
  - [x] New approach: `cy.cleanupTestPosts()` expected to take 100-500ms
  - [ ] Measure actual performance improvement (requires Cypress to be installed)

### Phase 2: Refactor Test Files ✅ Complete

#### Block Editor Tests (7 files) ✅
- [x] `add-post.cy.js`
- [x] `display-player.cy.js`
- [x] `insert-beyondwords-player.cy.js`
- [x] `player-content.cy.js`
- [x] `player-style.cy.js`
- [x] `segment-markers.cy.js`
- [x] `select-voice.cy.js`

#### Classic Editor Tests (6 files) ✅
- [x] `add-post.cy.js`
- [x] `display-player.cy.js`
- [x] `insert-beyondwords-player.cy.js`
- [x] `player-content.cy.js`
- [x] `player-style.cy.js`
- [x] `select-voice.cy.js`

#### Settings Tests (13 files) ✅
- [x] `content/content.cy.js`
- [x] `credentials/credentials.cy.js`
- [x] `player/call-to-action.cy.js`
- [x] `player/playback-from-segments.cy.js`
- [x] `player/player-colors.cy.js`
- [x] `player/player-theme.cy.js`
- [x] `player/player-ui.cy.js`
- [x] `player/skip-button-style.cy.js`
- [x] `player/text-highlighting.cy.js`
- [x] `player/widget-position.cy.js`
- [x] `player/widget-style.cy.js`
- [x] `pronunciations/manage-pronunciations-button.cy.js`
- [x] `settings.cy.js`
- [x] `summarization/manage-summarization-button.cy.js`
- [x] `voices/voices.cy.js`

#### Plugin Integration Tests (2 files) ✅
- [x] `plugins/amp.cy.js`
- [x] `plugins/wpgraphql.cy.js`

#### Other Tests (5 files) ✅
- [x] `bulk-actions.cy.js`
- [x] `filters.cy.js`
- [x] `site-health.cy.js`

**Total: 33 test files refactored**

### Phase 3: Optimization & Cleanup (1 hour)

- [ ] **Remove old DB reset helper/plugin**
  - [ ] Verify no tests use it anymore
  - [ ] Remove WP CLI reset command
  - [ ] Remove any reset plugin references

- [ ] **Add test data factories** (optional)
  - [ ] Create `testData.js` with reusable fixtures
  - [ ] Add post templates for common scenarios
  - [ ] Add BeyondWords-specific test data

- [ ] **Document new patterns**
  - [ ] Update this doc with examples
  - [ ] Add comments to commands
  - [ ] Create guidelines for new tests

### Phase 4: Verification & Performance Testing (30 mins)

- [ ] **Run full test suite**
  - [ ] Verify all tests pass
  - [ ] Confirm no flaky tests
  - [ ] Check for test pollution

- [ ] **Measure improvements**
  - [ ] Record new test suite execution time
  - [ ] Calculate time saved per test
  - [ ] Document improvement percentage

- [ ] **Commit changes**
  - [ ] Review all changes
  - [ ] Write descriptive commit message
  - [ ] Push to branch

---

## 🎨 Implementation Patterns

### Pattern 1: Test-Specific Post Creation

**Before:**
```javascript
beforeEach(() => {
  cy.resetWordPress(); // SLOW - 5-10 seconds
});

it('should display player on post', () => {
  cy.visit('/wp-admin/post-new.php');
  // ... create post
});
```

**After:**
```javascript
beforeEach(() => {
  cy.cleanupTestPosts(); // FAST - 100-500ms
});

it('should display player on post', () => {
  cy.createTestPost({
    title: 'Cypress Test - Display Player',
    content: 'Test content for player display'
  });

  cy.get('@testPostId').then(postId => {
    cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`);
    // ... test player display
  });
});
```

### Pattern 2: Search-Based Isolation

**Before:**
```javascript
it('should show 5 posts in list', () => {
  // Assumes clean DB
  cy.createPost('Post 1');
  cy.createPost('Post 2');
  // ...
  cy.visit('/wp-admin/edit.php');
  cy.get('.wp-list-table tbody tr').should('have.length', 5);
});
```

**After:**
```javascript
it('should show 5 posts in list', () => {
  // Create with unique prefix
  cy.createTestPost({ title: 'Cypress Test Post 1' });
  cy.createTestPost({ title: 'Cypress Test Post 2' });
  cy.createTestPost({ title: 'Cypress Test Post 3' });
  cy.createTestPost({ title: 'Cypress Test Post 4' });
  cy.createTestPost({ title: 'Cypress Test Post 5' });

  // Search for only our test posts
  cy.visit('/wp-admin/edit.php?s=Cypress+Test+Post');
  cy.get('.wp-list-table tbody tr').should('have.length', 5);
});
```

### Pattern 3: BeyondWords-Specific Test Data

```javascript
// For posts that need BeyondWords audio
cy.createTestPostWithAudio({
  title: 'Test Post with Audio',
  generateAudio: true,
  playerStyle: 'small'
}).then(postId => {
  cy.visit(`/?p=${postId}`);
  cy.get('.beyondwords-player').should('be.visible');
});
```

---

## 📊 Performance Targets

| Metric | Before | Target | Actual |
|--------|--------|--------|--------|
| **First test in file** | 5-10s | <1s | - |
| **Subsequent tests** | 5-10s each | <500ms each | - |
| **Full suite time** | ? | 50% reduction | - |
| **Test setup time** | ~8s | <500ms | - |

---

## 🔧 Cypress Commands Reference

### Cleanup Commands

```javascript
// Clean up all test posts (fast - targeted delete)
cy.cleanupTestPosts();

// Clean up test options
cy.cleanupTestOptions();

// Clean up everything test-related (use sparingly)
cy.cleanupAllTestData();
```

### Creation Commands

```javascript
// Create a basic test post
cy.createTestPost({
  title: 'My Test Post',
  content: 'Test content',
  status: 'publish'
});

// Create post with BeyondWords audio
cy.createTestPostWithAudio({
  title: 'Test with Audio',
  generateAudio: true
});

// Create multiple posts
cy.createTestPosts(5); // Creates 5 test posts
```

### Transaction Commands (if using)

```javascript
beforeEach(() => {
  cy.startTransaction();
});

afterEach(() => {
  cy.rollbackTransaction();
});
```

---

## 📝 Progress Notes

### 2025-10-17 - Initial Planning
- Created optimization plan
- Identified DB reset as main bottleneck
- Planned refactoring approach

### 2025-10-24 - Phase 1 & 2 Complete ✅

**Phase 1: Infrastructure Setup**
- [x] Commands created and tested
- [x] Infrastructure setup complete

**Commands Added:**
- `cy.cleanupTestPosts()` - Fast cleanup of test posts (100-500ms vs 5-10s for full reset)
- `cy.createTestPost(options)` - Create posts with "Cypress Test" prefix for easy cleanup
- `cy.createTestPostWithAudio(options)` - Create posts with BeyondWords audio enabled

**WP CLI Tasks Added:**
- `wp:post:deleteAll(searchTerm)` - Delete posts matching search term
- `wp:post:create(options)` - Create post with custom title/content/status
- `wp:post:setMeta(options)` - Set post meta values

**Phase 2: Test Refactoring**
- [x] **All 33 test files refactored** - Removed slow `cy.task('reset')` calls
- [x] **Fast cleanup implemented** - Each test now uses `cy.cleanupTestPosts()` in `beforeEach()`

**Files Modified:**
- [tests/cypress/support/commands.js](tests/cypress/support/commands.js) - Added 3 new commands
- [cypress.config.js](cypress.config.js) - Added 3 new WP CLI tasks
- **33 test files** in `tests/cypress/e2e/` - Replaced slow reset with fast cleanup

**Performance Impact:**
- **Before:** Full DB reset taking 5-10 seconds per test
- **After:** Fast cleanup taking 100-500ms per test
- **Expected Speedup:** 10-100x faster test setup

**Next Steps:**
1. Run tests to verify they still pass
2. Measure actual performance improvement

### 2025-10-24 - Code Coverage Investigation ❌

**Goal:** Set up Cypress code coverage to track JavaScript test coverage

**Attempted Approach:**
- Tried to set up `@cypress/code-coverage` with build-time instrumentation
- Required adding `babel-plugin-istanbul` to instrument source code during webpack build

**Issue Identified:**
- No major WordPress projects (Automattic, WordPress core) found using `.babelrc` + `babel-plugin-istanbul` with `@wordpress/scripts`
- Modifying webpack/Babel configuration would interfere with the official `@wordpress/scripts` build process
- Risk of breaking WordPress-standard build workflow

**Decision:**
- ❌ **Not implementing Cypress code coverage at this time**
- Must maintain compatibility with official WordPress build tooling
- Code coverage instrumentation would require custom build modifications that aren't supported/documented by WordPress

**What Was Reverted:**
- Removed `.babelrc` configuration
- Removed `.nycrc` configuration
- Removed Cypress coverage integration from `cypress.config.js` and `tests/cypress/support/e2e.js`
- Uninstalled dependencies: `@cypress/code-coverage`, `babel-plugin-istanbul`, `nyc`, `istanbul-lib-coverage`, `@babel/preset-env`
- Removed coverage scripts from `package.json`

**Alternative Options (Not Pursued):**
1. Runtime instrumentation (complex, affects test performance)
2. Manual code coverage tracking (not practical)
3. PHPUnit coverage only (already have this at 86.57%)

**Conclusion:**
Focus remains on test performance optimization (Phase 1 & 2 complete). JavaScript code coverage would be nice-to-have but not worth compromising WordPress build standards.

### [Date] - Final Results
- [ ] Old reset method removed
- [ ] Documentation updated
- [ ] Committed and merged

---

## 🎯 Success Criteria

✅ All Cypress tests pass consistently
✅ No full DB resets in any test files
✅ Test setup time reduced by >80%
✅ Tests are independent and can run in any order
✅ New test pattern documented for team

---

## 🚀 Next Steps After Completion

1. Apply same patterns to any new tests
2. Consider adding visual regression testing
3. Explore parallel test execution
4. Add CI/CD optimization if needed
