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

### Phase 1: Setup Infrastructure (30 mins)

- [ ] **Create Cypress commands for fast cleanup**
  - [ ] Add `cy.cleanupTestPosts()` command
  - [ ] Add `cy.createTestPost()` command with unique naming
  - [ ] Add `cy.createTestPostWithAudio()` command for BeyondWords tests
  - [ ] Test commands work correctly

- [ ] **Add database transaction support** (optional/experimental)
  - [ ] Add `cy.startTransaction()` command
  - [ ] Add `cy.rollbackTransaction()` command
  - [ ] Test with a simple spec to verify it works

- [ ] **Create baseline performance measurement**
  - [ ] Record current test suite execution time
  - [ ] Record time spent in DB reset vs actual tests

### Phase 2: Refactor Test Files (2-3 hours)

#### Block Editor Tests
- [ ] **`display-player.cy.js`** - Refactor to use test-specific posts
  - [ ] Remove full DB reset from beforeEach
  - [ ] Use `cy.createTestPost()` in each test
  - [ ] Add targeted cleanup in beforeEach
  - [ ] Verify tests still pass
  - [ ] Measure performance improvement

- [ ] **Other block editor tests** (if any)
  - [ ] Identify all block editor test files
  - [ ] Apply same pattern
  - [ ] Verify tests pass

#### Admin/Post List Tests
- [ ] **Post list screen tests** - Most affected by DB reset
  - [ ] Replace DB reset with `cy.cleanupTestPosts()`
  - [ ] Use search/filters to isolate test posts
  - [ ] Update assertions to count only test posts
  - [ ] Verify tests pass

- [ ] **Settings screen tests**
  - [ ] Identify if DB reset is needed
  - [ ] Replace with option cleanup if applicable
  - [ ] Verify tests pass

#### Integration Tests
- [ ] **Audio generation tests**
  - [ ] Create posts with unique identifiers
  - [ ] Clean up only test posts
  - [ ] Verify tests pass

- [ ] **Bulk actions tests**
  - [ ] Create known set of test posts
  - [ ] Verify bulk actions work on test posts only
  - [ ] Clean up test posts

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

### [Date] - Phase 1 Complete
- [ ] Commands created and tested
- [ ] Baseline measurements recorded
- [ ] Ready for test refactoring

### [Date] - Phase 2 Complete
- [ ] All test files refactored
- [ ] Tests passing
- [ ] Performance improvements verified

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
