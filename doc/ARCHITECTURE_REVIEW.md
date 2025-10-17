# Comprehensive Architectural Review: BeyondWords WordPress Plugin

**Date:** 2025-10-17
**Status:** ⏸️ Paused - Ready to Continue
**Overall Grade:** B+
**Progress:** Phase 1 - 1/5 tasks completed (20%)

---

## 🚀 How to Continue

To resume this architectural refactoring, use this prompt:

```
Continue with doc/ARCHITECTURE_REVIEW.md
```

**Next Recommended Task:** Phase 1, Task 3 - Rename Utils Classes (Quick Win, 1-2 hours)

### Quick Reference

| Phase | Task | Status | Effort | Priority |
|-------|------|--------|--------|----------|
| 1 | Rename Post/Posts | ✅ Skipped | N/A | Low |
| 1 | Delete Response.php | ✅ Done | 5 min | Done |
| 1 | **Rename Utils Classes** | ⏭️ **Next** | 1-2 hrs | 🟢 Quick Win |
| 1 | Add README files | ❌ Pending | 2-3 hrs | 🟢 Quick Win |
| 1 | Split CoreUtils | ❌ Pending | 3-4 hrs | 🟢 Quick Win |
| 2 | Refactor SettingsUtils | ❌ Pending | 1-2 days | 🟡 Medium |
| 2 | Refactor PostMetaUtils | ❌ Pending | 1 day | 🟡 Medium |
| 2 | Rename Core.php | ❌ Pending | 2-3 days | 🟡 Medium |
| 2 | Add Interfaces | ❌ Pending | 1 day | 🟡 Medium |
| 3 | **Split ApiClient** | ❌ Pending | 2-3 days | 🔴 High Impact |
| 3 | Reorganize Core/ | ❌ Pending | 2-3 days | 🔴 Large |
| 3 | Create Value Objects | ❌ Pending | 1 week | 🔵 Optional |

See [Progress Tracking](#progress-tracking) section below for full details.

---

## Executive Summary

This WordPress plugin demonstrates a **transition state** between traditional WordPress plugin architecture and modern PHP standards. The codebase shows strong fundamentals with PSR-4 autoloading, PHP 8.1+ type declarations, and clear separation of concerns. However, from a Symfony/Laravel developer's perspective, there are significant opportunities to modernize the architecture while maintaining WordPress compatibility.

### Key Strengths
- ✅ Excellent use of modern PHP (strict types, typed properties, union types)
- ✅ Clean PSR-4 namespacing structure
- ✅ Static method pattern provides familiar WordPress-style API
- ✅ Good separation between Core and Component layers
- ✅ WordPress compatibility mindset throughout

### Key Concerns
- ⚠️ Generic/vague naming ("Core", "Utils" classes)
- ⚠️ ApiClient is a "god class" doing too much (664 lines, complexity 61/50)
- ⚠️ Utils classes mixing concerns instead of focused services
- ⚠️ Limited use of interfaces/contracts
- ~~⚠️ Response.php class appears unused~~ ✅ **FIXED 2025-10-17**
- ~~⚠️ Inconsistent naming (Post vs Posts components)~~ ✅ **ACCEPTED AS-IS 2025-10-17** (WordPress convention)

---

## 1. Directory Structure Analysis

### Current Structure

```
src/
├── Compatibility/           # Third-party integrations
│   └── WPGraphQL/
├── Component/               # Feature modules
│   ├── Plugin/             # Editor UI components
│   ├── Post/               # Single post features (15+ subdirs)
│   ├── Posts/              # Post list features (2 subdirs)
│   ├── Settings/           # Plugin settings (complex tree)
│   └── SiteHealth/         # WordPress health checks
└── Core/                   # Core functionality
    ├── ApiClient.php       # HTTP client + API methods
    ├── Core.php            # Main business logic
    ├── CoreUtils.php       # Utility methods
    ├── Environment.php     # Configuration
    ├── Player/             # Player rendering
    ├── Request.php         # HTTP request object
    └── Response.php        # HTTP response object (unused?)
```

### Strengths
- ✅ Clear separation between Component (features) and Core (infrastructure)
- ✅ Logical grouping of related features in subdirectories
- ✅ Compatibility layer properly isolated
- ✅ WordPress-friendly structure

### Issues

#### 🔴 Critical: Post vs Posts Naming
**Location:** `Component/Post/` vs `Component/Posts/`

**Problem:**
- `Post/` contains features for editing a single post (15+ subdirectories)
- `Posts/` contains features for the posts list screen (2 subdirectories)
- From a modern PHP perspective, this looks like a typo or inconsistency

**Recommendation:** Rename to `Component/Post/Editor/` and `Component/Post/List/`

**Status:** ❌ Not Started

---

#### 🟡 Medium: "Core" is Too Generic
**Location:** `src/Core/`

**Problem:**
- What makes something "Core" vs "Component"?
- ApiClient, Environment, Request/Response feel like HTTP infrastructure, not "Core business logic"
- In Symfony, "Kernel" is specific; "Core" is vague

**Recommendation:** Reorganize into focused directories:
```
Core/
├── Http/                 # HTTP layer (ApiClient, Request, Response)
├── Audio/                # Audio domain (generation, lifecycle)
├── Detection/            # Detection services (Editor, AMP, etc.)
├── Registry/             # Registries (metadata, options)
└── Player/               # Player rendering
```

**Status:** ❌ Not Started

---

#### 🟢 Minor: JS/PHP Co-location Inconsistency
**Observation:**
- Some directories co-locate JS and PHP (e.g., `Component/Post/Panel/Inspect/`)
- Others are PHP-only
- No clear convention

**Recommendation:** Add README.md files documenting relationships

**Status:** ❌ Not Started

---

## 2. Naming Conventions Analysis

### Class Name Review

| Class | Current Name | Issue | Recommended Name | Priority | Status |
|-------|-------------|-------|------------------|----------|--------|
| Core.php | ⚠️ Generic | Too vague | `AudioGenerationService` + `PluginBootstrap` | 🟡 Medium | ❌ Not Started |
| CoreUtils.php | ❌ Anti-pattern | Dumping ground | Split into `EditorDetector`, `RequestDetector`, `MetadataRegistry` | 🟢 Quick Win | ❌ Not Started |
| ApiClient.php | ⚠️ Misleading | Not just a client | Split into layered services | 🔴 Critical | ❌ Not Started |
| Environment.php | ✅ Good | Clear purpose | Maybe `EnvironmentConfig` | 🔵 Optional | ❌ Not Started |
| PostMetaUtils.php | ⚠️ Vague | Utils unclear | `PostMetadataRepository` | 🟢 Quick Win | ❌ Not Started |
| SettingsUtils.php | ⚠️ Vague | Utils unclear | Split into multiple services | 🟡 Medium | ❌ Not Started |
| PostContentUtils.php | ⚠️ Vague | Utils unclear | `ContentTransformer` + `BlockProcessor` | 🟢 Quick Win | ❌ Not Started |
| Response.php | ❌ Unused | Dead code | Delete if unused | 🟢 Quick Win | ❌ Not Started |

---

## 3. Utils Classes Deep Dive

### 3.1 CoreUtils.php - Split Required

**Location:** `src/Core/CoreUtils.php`
**Current Responsibilities:**
- Detecting Gutenberg editor (`isGutenbergPage()`)
- Detecting edit screens (`isEditScreen()`)
- Detecting AMP requests (`isAmp()`)
- Managing post meta key registry (`getPostMetaKeys()`)
- Managing options registry (`getOptions()`)

**Problem:** This is a **junk drawer** - unrelated static methods grouped by convenience.

**Recommended Split:**

```php
// Detection services
class EditorDetector {
    public static function isBlockEditor(): bool { }
    public static function isEditScreen(): bool { }
}

class RequestDetector {
    public static function isAmp(): bool { }
}

// Registry services
class MetadataRegistry {
    public static function getPostMetaKeys(string $type = 'current'): array { }
    public static function getOptions(string $type = 'current'): array { }
}
```

**Complexity:** 🟢 Quick win - Simple file split, no logic changes
**Estimated Effort:** 2-3 hours
**Status:** ❌ Not Started

---

### 3.2 PostMetaUtils.php - Refactor to Repository

**Location:** `src/Component/Post/PostMetaUtils.php`
**Current Responsibilities:**
- CRUD operations on post metadata
- Legacy data migration (speechkit_* → beyondwords_*)
- Business logic (hasContent, hasGenerateAudio)
- Data retrieval with fallbacks

**Problem:** This is actually a **repository pattern** in disguise, but named poorly.

**Recommended Refactor:**

```php
class PostMetadataRepository {
    public function getContentId(int $postId, bool $fallback = false): string|int|false { }
    public function getProjectId(int $postId, bool $strict = false): int|string|false { }
    public function getAllMetadata(int $postId): array { }
    public function removeAllMetadata(int $postId): void { }
}

class PostMetadataMigration {
    public function getRenamedMeta(int $postId, string $name): mixed { }
}

class PostAudioStatusChecker {
    public function hasContent(int $postId): bool { }
    public function hasGenerateAudio(int $postId): bool { }
}
```

**Complexity:** 🟡 Medium effort - Requires moving business logic
**Estimated Effort:** 1 day
**Status:** ❌ Not Started

---

### 3.3 SettingsUtils.php - Multiple Services Required

**Location:** `src/Component/Settings/SettingsUtils.php`
**Current Responsibilities:**
- Post type filtering/validation
- API credential validation
- API connection checking
- HTML rendering (colorInput method!)
- Error message management

**Problem:** Violates **Single Responsibility Principle** badly.

**Recommended Split:**

```php
class PostTypeRegistry {
    public static function getConsideredPostTypes(): array { }
    public static function getCompatiblePostTypes(): array { }
    public static function getIncompatiblePostTypes(): array { }
}

class ApiCredentialsValidator {
    public static function hasCredentials(): bool { }
    public static function hasValidConnection(): bool { }
    public static function validateConnection(): bool { }
}

class SettingsErrorHandler {
    public static function addError(string $message, string $errorId = ''): void { }
}
```

**Complexity:** 🟡 Medium effort - Many references to update
**Estimated Effort:** 1-2 days
**Status:** ❌ Not Started

---

### 3.4 PostContentUtils.php - Just Rename

**Location:** `src/Component/Post/PostContentUtils.php`
**Assessment:** Actually **well-scoped** - all methods relate to content transformation. The "Utils" suffix is the only problem.

**Recommended Action:**

```php
// Simple rename
class ContentTransformer {
    public static function getContentBody(int|\WP_Post $post): string|null { }
    public static function getContentParams(int $postId): array|string { }
    // etc.
}

// Optional: Split further if desired
class BlockProcessor {
    public static function getAudioEnabledBlocks(int|\WP_Post $post): array { }
    public static function addMarkerAttribute(string $html, string $marker): string { }
}
```

**Complexity:** 🟢 Quick win - Just rename, already well-organized
**Estimated Effort:** 1-2 hours
**Status:** ❌ Not Started

---

## 4. ApiClient.php - The God Class

**Location:** `src/Core/ApiClient.php`
**Lines:** 664
**Complexity Score:** 61/50 (22% over threshold)
**Suppression:** `@SuppressWarnings(PHPMD.ExcessiveClassComplexity)`

### Current Responsibilities
- HTTP request building
- 15+ API endpoint methods (GET/POST/PUT/DELETE)
- Response parsing
- Error handling and logging
- Post meta updates
- Business logic (validation, error messages)

### Problem
This is a **code smell** - suppressing warnings instead of fixing the design. The class mixes:
- Transport layer (HTTP)
- Business logic (what to send, what to do with response)
- Persistence layer (saving to post meta)

### Recommended Architecture

```php
// 1. HTTP Client (transport layer)
class BeyondWordsHttpClient {
    public function request(string $method, string $url, array $options = []): Response { }
    private function buildRequest(string $method, string $url, array $options): Request { }
    private function handleWordPressError(\WP_Error $error): never { }
}

// 2. API Service (domain layer)
class BeyondWordsApiService {
    public function __construct(
        private BeyondWordsHttpClient $client,
        private EnvironmentConfig $config
    ) {}

    public function getContent(string $contentId, ?string $projectId = null): array { }
    public function createAudio(int $postId): array { }
    public function updateAudio(int $postId): array { }
}

// 3. Request Builder (for complex requests)
class ContentRequestBuilder {
    public function buildCreateRequest(int $postId): array { }
    public function buildUpdateRequest(int $postId): array { }
}

// 4. Response Handler (post-processing)
class AudioResponseHandler {
    public function processResponse(array $response, int $postId): void {
        // Save content_id, preview_token, etc to post meta
    }
}
```

### Benefits
- **Separation of concerns:** HTTP vs business logic vs persistence
- **Testability:** Each class can be mocked/tested independently
- **Maintainability:** Changes to one endpoint don't affect others
- **WordPress compatibility:** Still uses wp_remote_request under the hood

**Complexity:** 🔴 Large refactor
**Estimated Effort:** 2-3 days
**Priority:** High (removes suppression, improves maintainability)
**Status:** ❌ Not Started

---

## 5. Environment.php Analysis

**Location:** `src/Core/Environment.php`
**Assessment:** ✅ Already well-designed

### Current Implementation
```php
class Environment {
    public const BEYONDWORDS_API_URL = 'https://api.beyondwords.io/v1';

    public static function getApiUrl(): string {
        if (defined('BEYONDWORDS_API_URL') && strlen(BEYONDWORDS_API_URL)) {
            return BEYONDWORDS_API_URL;
        }
        return static::BEYONDWORDS_API_URL;
    }
}
```

### Recommendation
**Keep as-is** - Already WordPress-friendly and serves its purpose well.

**Optional Enhancement:** Rename to `EnvironmentConfig` for clarity

**Status:** ✅ No Action Required

---

## 6. Core.php Analysis

**Location:** `src/Core/Core.php`
**Lines:** 427
**Current Responsibilities:**
- WordPress hook registration
- Post status validation
- Audio generation orchestration
- API response processing
- Block editor asset enqueuing
- Meta field registration
- Post deletion handling

### Problem
"Core" is **too generic and too broad**:
- What is "core" to this plugin? Everything!
- Mixes WordPress integration (hooks) with business logic
- Mixes UI (enqueuing assets) with data processing

### Recommended Refactor

```php
// 1. Service for audio generation (business logic)
class AudioGenerationService {
    public function shouldGenerateAudio(int $postId): bool { }
    public function generateAudio(int $postId): array|false { }
    public function deleteAudio(int $postId): array|false { }
}

// 2. Service for post lifecycle (orchestration)
class PostLifecycleHandler {
    public function onPostSaved(int $postId): void { }
    public function onPostTrashed(int $postId): void { }
    public function onPostDeleted(int $postId): void { }
}

// 3. WordPress integration (hooks only)
class PluginBootstrap {
    public static function init(): void {
        // Register hooks
        add_action('wp_after_insert_post', [PostLifecycleHandler::class, 'onPostSaved'], 99);
        add_action('wp_trash_post', [PostLifecycleHandler::class, 'onPostTrashed']);
    }

    public static function registerBlockEditorAssets(): void { }
    public static function registerPostMeta(): void { }
}
```

**Complexity:** 🔴 Large refactor
**Estimated Effort:** 3-4 days
**Status:** ❌ Not Started

---

## 7. Static Methods Usage

### Current Pattern
**Everywhere:** Almost all classes use static methods exclusively.

### Analysis

✅ **Benefits:**
- Familiar to WordPress developers
- Simple to call from anywhere
- No DI required
- Performance (no object creation overhead)

⚠️ **Drawbacks:**
- Hard to test (cannot mock static calls easily)
- Hidden dependencies
- No polymorphism
- Tight coupling

### Recommendation: Hybrid Approach

```php
// Static facade for WordPress developers
class BeyondWords {
    private static ?AudioGenerationService $audioService = null;

    public static function generateAudio(int $postId): array|false {
        return self::getAudioService()->generate($postId);
    }

    private static function getAudioService(): AudioGenerationService {
        if (!self::$audioService) {
            self::$audioService = new AudioGenerationService(
                new BeyondWordsApiService(...),
                new PostMetadataRepository()
            );
        }
        return self::$audioService;
    }
}

// Actual service (testable)
class AudioGenerationService {
    public function __construct(
        private BeyondWordsApiService $api,
        private PostMetadataRepository $metadata
    ) {}

    public function generate(int $postId): array|false {
        // Business logic here
    }
}
```

**Benefits:** WordPress devs still call `BeyondWords::generateAudio($id)`, but tests can instantiate services with mocks.

**Status:** 🔵 Optional Enhancement

---

## 8. Response.php Investigation

**Location:** `src/Core/Response.php`
**Issue:** Class appears **UNUSED** in the codebase

### Investigation Results
```bash
$ grep -r "use.*Response" src/
# Only found in Response.php itself
```

- ❌ ApiClient methods return arrays or `\WP_Error`, not `Response` objects
- ❌ No imports of `Beyondwords\Wordpress\Core\Response`
- ❌ Class has no tests

### Recommendation
**Delete** `Response.php` if truly unused, OR document if it's for future use

**Complexity:** 🟢 Quick win
**Estimated Effort:** 5 minutes
**Status:** ❌ Not Started

---

## Recommended Action Plan

### Phase 1: Quick Wins (1-2 days) 🟢

**Estimated Total Effort:** 1-2 days

1. ⚠️ **Rename Post/Posts Components** - **SKIPPED**
   - **Original Plan:** `Component/Post/` → `Component/Post/Editor/`, `Component/Posts/` → `Component/Post/List/`
   - **Issue Identified (2025-10-17):** The name "Editor" is misleading
     - Most `Component/Post/*` components are admin-focused (metaboxes, Block Editor, Classic Editor)
     - BUT they're not just for "editing" - they handle save hooks, metadata, business logic
     - Frontend rendering is in `Core/Player/`, not `Component/Post/`
     - The singular/plural distinction (`Post` vs `Posts`) actually makes sense in WordPress context
   - **Decision:** **Current structure is acceptable** - Not worth the refactoring cost
   - **Better Alternative:** Add a `Component/Post/README.md` explaining the structure
   - **Status:** ✅ **DECISION MADE** - Skip this task, focus on higher-value refactorings

2. ✅ **Delete Response.php**
   - Remove unused code
   - **Effort:** 5 minutes
   - **Status:** ✅ **COMPLETED 2025-10-17**
   - **Files removed:** `src/Core/Response.php`, `tests/phpunit/Core/ResponseTest.php`
   - **Impact:** Test count reduced from 434 to 429 (removed 5 Response tests)
   - **Verification:** All remaining tests passing ✅

3. ✅ **Rename Utils Classes** (just rename, don't split)
   - `PostMetaUtils` → `PostMetadataRepository`
   - `PostContentUtils` → `ContentTransformer`
   - **Effort:** 1-2 hours
   - **Status:** ❌ Not Started

4. ✅ **Add Component README Files**
   - Document PHP ↔ JS relationships
   - Explain co-location decisions
   - **Effort:** 2-3 hours
   - **Status:** ❌ Not Started

5. ✅ **Split CoreUtils**
   - → `EditorDetector`, `RequestDetector`, `MetadataRegistry`
   - **Effort:** 3-4 hours
   - **Status:** ❌ Not Started

---

### Phase 2: Medium Effort (1 week) 🟡

**Estimated Total Effort:** 1 week

6. **Refactor SettingsUtils**
   - → `PostTypeRegistry`, `ApiCredentialsValidator`, `SettingsErrorHandler`
   - **Effort:** 1-2 days
   - **Status:** ❌ Not Started

7. **Refactor PostMetaUtils**
   - → `PostMetadataRepository`, `PostMetadataMigration`, `PostAudioStatusChecker`
   - **Effort:** 1 day
   - **Status:** ❌ Not Started

8. **Rename Core.php**
   - → `AudioGenerationService` + `PluginBootstrap`
   - Split WordPress hooks from business logic
   - **Effort:** 2-3 days
   - **Status:** ❌ Not Started

9. **Add Interfaces** for key abstractions
   - `ApiClientInterface`, `MetadataRepositoryInterface`
   - **Effort:** 1 day
   - **Status:** ❌ Not Started

---

### Phase 3: Large Refactors (2-4 weeks) 🔴

**Estimated Total Effort:** 2-4 weeks

10. **Split ApiClient** into layered services
    - → `BeyondWordsHttpClient`, `BeyondWordsApiService`, `ContentRequestBuilder`, `AudioResponseHandler`
    - **Effort:** 2-3 days
    - **Priority:** High (removes suppression)
    - **Status:** ❌ Not Started

11. **Reorganize Core/ Directory Structure**
    - Create focused subdirectories: Http/, Audio/, Detection/, Registry/
    - **Effort:** 2-3 days
    - **Status:** ❌ Not Started

12. **Create Value Objects** for domain concepts
    - `AudioContent`, `ProjectSettings`, `VoiceConfiguration`
    - **Effort:** 1 week
    - **Status:** 🔵 Optional

---

## Proposed Final Directory Structure

```
src/
├── Compatibility/              # Third-party integrations
│   └── WPGraphQL/
│
├── Component/                  # Feature modules
│   ├── Plugin/                # Editor plugins
│   │   └── Panel/
│   │
│   ├── Post/                  # Single post features
│   │   ├── Editor/           # Post editor UI (was Post/)
│   │   │   ├── AddPlayer/
│   │   │   ├── BlockAttributes/
│   │   │   ├── Panel/
│   │   │   └── README.md     # Documents editor components
│   │   │
│   │   ├── List/             # Posts list screen (was Posts/)
│   │   │   ├── BulkEdit/
│   │   │   ├── Column/
│   │   │   └── README.md
│   │   │
│   │   └── Domain/           # Shared post services
│   │       ├── PostMetadataRepository.php
│   │       ├── ContentTransformer.php
│   │       └── PostAudioStatusChecker.php
│   │
│   ├── Settings/              # Plugin settings
│   │   ├── Fields/
│   │   ├── Tabs/
│   │   ├── SettingsErrorHandler.php
│   │   └── PostTypeRegistry.php
│   │
│   └── SiteHealth/            # WordPress health checks
│
├── Core/                      # Core services
│   ├── Http/                 # HTTP layer
│   │   ├── BeyondWordsHttpClient.php
│   │   ├── BeyondWordsApiService.php
│   │   ├── ContentRequestBuilder.php
│   │   ├── AudioResponseHandler.php
│   │   └── Request.php
│   │
│   ├── Audio/                # Audio domain
│   │   ├── AudioGenerationService.php
│   │   └── PostLifecycleHandler.php
│   │
│   ├── Detection/            # Detection services
│   │   ├── EditorDetector.php
│   │   └── RequestDetector.php
│   │
│   ├── Registry/             # Registries
│   │   └── MetadataRegistry.php
│   │
│   ├── Player/               # Player rendering
│   │   ├── ConfigBuilder.php
│   │   ├── Player.php
│   │   └── Renderer/
│   │
│   ├── EnvironmentConfig.php # Configuration
│   ├── PluginBootstrap.php   # WordPress integration (was Core.php)
│   ├── Updater.php
│   └── Uninstaller.php
│
└── Plugin.php                 # Main entry point
```

---

## What NOT to Change ❌

Based on WordPress ecosystem considerations:

1. ❌ **Don't add a full DI container** - Overkill for WordPress plugins
2. ❌ **Don't replace all static methods** - WordPress devs expect them
3. ❌ **Don't add Symfony/Laravel dependencies** - Avoid bloat and conflicts
4. ❌ **Don't fight WordPress conventions** - Hooks, options, post meta are correct
5. ❌ **Don't use PSR-7 HTTP** - WordPress has its own HTTP API

---

## Progress Tracking

### Completed ✅

**Phase 1 Completed Tasks: 1/5 (20%)**

1. ✅ **Task 1: Post/Posts Component Naming** (2025-10-17)
   - **Decision:** SKIPPED - Current structure is acceptable
   - **Rationale:** "Post" vs "Posts" follows WordPress conventions, renaming would be misleading

2. ✅ **Task 2: Delete Response.php** (2025-10-17)
   - **Completed:** Removed `src/Core/Response.php` and test file
   - **Impact:** Test count reduced from 434 to 429
   - **Verification:** All tests passing ✅

### Next Up 🎯

**Recommended Next Task: Phase 1, Task 3**

**Task 3: Rename Utils Classes** (Estimated: 1-2 hours)
- Rename `PostMetaUtils` → `PostMetadataRepository`
- Rename `PostContentUtils` → `ContentTransformer`
- Update all references
- Quick win with high clarity improvement

**To start:** Simply say "Continue with doc/ARCHITECTURE_REVIEW.md" and I'll begin Task 3.

### Remaining Tasks ❌

**Phase 1 Remaining (3 tasks):**
- Task 4: Add Component README files
- Task 5: Split CoreUtils into focused classes

**Phase 2: Medium Effort (4 tasks)**
- All tasks pending (see Phase 2 section above)

**Phase 3: Large Refactors (3 tasks)**
- All tasks pending (see Phase 3 section above)

### Overall Status

- **Completed:** 2 tasks (1 skipped, 1 completed)
- **In Progress:** 0 tasks
- **Remaining:** 11 tasks across 3 phases
- **Estimated Total Remaining:** 3-5 weeks

---

## Notes

- This is a **living document** - update as work progresses
- Mark tasks as complete with ✅ and date
- Add notes about challenges or decisions made
- Link to related PRs or commits

---

**Last Updated:** 2025-10-17
**Next Review:** After Phase 1 completion
