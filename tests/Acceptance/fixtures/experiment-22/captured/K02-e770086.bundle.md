# Context bundle

bundle_version 1 · budget 8000 / used 504 tokens

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Auth/LoginAction.php` (lines 12-12)
**Tokens:** 13

```php
    public function execute(LoginDTO $dto): array
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Auth/LogoutAction.php` (lines 9-9)
**Tokens:** 12

```php
    public function execute(User $user): void
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Branch/CreateBranchAction.php` (lines 18-18)
**Tokens:** 15

```php
    public function execute(CreateBranchDTO $dto): Branch
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Branch/DeleteBranchAction.php` (lines 16-16)
**Tokens:** 11

```php
    public function execute(int $id): void
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Branch/GetBranchesAction.php` (lines 15-15)
**Tokens:** 11

```php
    public function execute(): Collection
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Branch/UpdateBranchAction.php` (lines 19-19)
**Tokens:** 17

```php
    public function execute(int $id, UpdateBranchDTO $dto): Branch
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/CreatePackageAction.php` (lines 18-18)
**Tokens:** 17

```php
    public function execute(CreatePackageDTO $dto): CateringPackage
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/CreateSampleMenuAction.php` (lines 18-18)
**Tokens:** 17

```php
    public function execute(CreateSampleMenuDTO $dto): SampleMenu
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/DeletePackageAction.php` (lines 16-16)
**Tokens:** 11

```php
    public function execute(int $id): void
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/DeleteSampleMenuAction.php` (lines 16-16)
**Tokens:** 11

```php
    public function execute(int $id): void
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/GetPackagesAction.php` (lines 15-15)
**Tokens:** 11

```php
    public function execute(): Collection
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/GetSampleMenusAction.php` (lines 15-15)
**Tokens:** 11

```php
    public function execute(): Collection
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/SubmitQuoteRequestAction.php` (lines 16-16)
**Tokens:** 18

```php
    public function execute(SubmitQuoteRequestDTO $dto): QuoteRequest
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/UpdatePackageAction.php` (lines 19-19)
**Tokens:** 19

```php
    public function execute(int $id, UpdatePackageDTO $dto): CateringPackage
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/UpdateQuoteRequestStatusAction.php` (lines 16-16)
**Tokens:** 21

```php
    public function execute(int $id, UpdateQuoteRequestStatusDTO $dto): QuoteRequest
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/Catering/UpdateSampleMenuAction.php` (lines 18-18)
**Tokens:** 19

```php
    public function execute(int $id, CreateSampleMenuDTO $dto): SampleMenu
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/DeliveryApp/CreateDeliveryAppAction.php` (lines 18-18)
**Tokens:** 17

```php
    public function execute(CreateDeliveryAppDTO $dto): DeliveryApp
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/DeliveryApp/DeleteDeliveryAppAction.php` (lines 16-16)
**Tokens:** 11

```php
    public function execute(int $id): void
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/DeliveryApp/GetDeliveryAppsAction.php` (lines 15-15)
**Tokens:** 16

```php
    public function execute(bool $activeOnly = true): Collection
```

## fetched · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/DeliveryApp/UpdateDeliveryAppAction.php` (lines 18-18)
**Tokens:** 19

```php
    public function execute(int $id, CreateDeliveryAppDTO $dto): DeliveryApp
```

## fetched · changed_signature

**Reason:** the signature of getByPage changed; its call sites are not shown by the diff
**Source:** `app/Actions/MediaItem/GetMediaItemsAction.php` (lines 22-22)
**Tokens:** 17

```php
            fn () => $this->repository->getByPage($page, $section)
```

## flagged · changed_signature

**Reason:** the signature of execute changed; its call sites are not shown by the diff
**Source:** `app/Actions/MediaItem/GetMediaItemsAction.php` :: `execute` (lines 12-20)
**Tokens:** 21

```text
ASSUMPTION: additional call sites exist beyond the search bound; not all verified
```

## fetched · changed_signature

**Reason:** the signature of getByPage changed; its call sites are not shown by the diff
**Source:** `app/Actions/PageContent/GetPageContentAction.php` (lines 23-23)
**Tokens:** 17

```php
            fn () => $this->repository->getByPage($page, $section)
```

## fetched · changed_signature

**Reason:** the signature of scopeForPage changed; its call sites are not shown by the diff
**Source:** `app/Models/MediaItem.php` (lines 24-24)
**Tokens:** 26

```php
    public function scopeForPage(Builder $query, ?string $page = null, ?string $section = null): Builder
```

## fetched · changed_signature

**Reason:** the signature of scopeForPage changed; its call sites are not shown by the diff
**Source:** `app/Models/PageContent.php` (lines 20-20)
**Tokens:** 24

```php
    public function scopeForPage(Builder $query, string $page, ?string $section = null): Builder
```

## fetched · changed_signature

**Reason:** the signature of getByPage changed; its call sites are not shown by the diff
**Source:** `app/Repositories/MediaItemRepository.php` (lines 10-10)
**Tokens:** 22

```php
    public function getByPage(?string $page = null, ?string $section = null): Collection
```

## fetched · changed_signature

**Reason:** the signature of getByPage changed; its call sites are not shown by the diff
**Source:** `app/Repositories/PageContentRepository.php` (lines 12-12)
**Tokens:** 20

```php
    public function getByPage(string $page, ?string $section = null): Collection
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Collection, whose contract is defined in another file
**Source:** `app/Actions/MediaItem/GetMediaItemsAction.php` :: `Illuminate\Support\Collection` (lines 12-20)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Builder, whose contract is defined in another file
**Source:** `app/Models/MediaItem.php` :: `Illuminate\Database\Eloquent\Builder` (lines 21-31)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Collection, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `Illuminate\Support\Collection` (lines 7-13)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
