# Context bundle

bundle_version 1 · budget 8000 / used 1630 tokens

## fetched · same_file_reference

**Reason:** the region calls the sibling member categories, whose contract the diff does not show
**Source:** `app/Http/Controllers/Admin/ProductController.php` :: `categories` (lines 134-137)
**Tokens:** 27

```php
    private function categories()
    {
        return $this->categoryService->getModelForSelect();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DataTables\CategoryDataTable, whose contract is defined in another file
**Source:** `app/DataTables/CategoryDataTable.php` :: `dataTable` (lines 17-57)
**Tokens:** 505

```php
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'dashboard.categories.action')
            ->filterColumn('name', function ($query, $keyword) {
                $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) like LOWER(?)", ["%$keyword%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))) like LOWER(?)", ["%$keyword%"]);
            })
            ->addColumn('position', function ($category) {
                return view('dashboard.categories.position', ['category' => $category]);
            })
            ->addColumn('name', function ($category) {
                return $category->name;
            })
            ->addColumn('sub_categories', function ($category) {
                return view('dashboard.categories.sub_categories', ['category' => $category]);
            })
            ->addColumn('image', function ($category) {
                return " <img src='{$category->image}'  width='60' height='60'>";
            })
            ->addColumn('status', function ($category) {
                return view('dashboard.categories.status', ['category' => $category]);
            })
            ->addColumn('international', function ($category) {
                return view('dashboard.categories.international', ['category' => $category]);
            })
            ->addColumn('home', function ($category) {
                return view('dashboard.categories.in_home', ['category' => $category]);
            })
            ->addColumn('date', function ($category) {
                return $category->created_at->format('d/m/Y');
            })
            ->addIndexColumn()
            ->rawColumns(['position' , 'name', 'sub_categories', 'image', 'date', 'status', 'home', 'action'])
            ->setRowId('id');
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DataTables\CategoryDataTable, whose contract is defined in another file
**Source:** `app/DataTables/CategoryDataTable.php` :: `filename` (lines 131-137)
**Tokens:** 38

```php
    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Category_' . date('YmdHis');
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DataTables\CategoryDataTable, whose contract is defined in another file
**Source:** `app/DataTables/CategoryDataTable.php` :: `getColumns` (lines 112-129)
**Tokens:** 193

```php
    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('position')->title('#'),
            Column::computed('DT_RowIndex')->title('#'),
            Column::make('name')->title(__('Name')),
            Column::make('sub_categories')->title(__('Sub Categories'))->searchable(),
            Column::computed('image')->title(__('Image')),
            Column::computed('status')->title(__('Status')),
            Column::computed('international')->title(__('International')),
            Column::computed('home')->title(__('Appearing')),
            Column::computed('date')->title(__('Created')),
            Column::computed('action')->title(__('Actions')),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DataTables\CategoryDataTable, whose contract is defined in another file
**Source:** `app/DataTables/CategoryDataTable.php` :: `html` (lines 68-108)
**Tokens:** 392

```php
    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('category-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip') // Include 'l' for the length menu
            ->orderBy(0)
            ->responsive(true)
            ->selectStyleSingle()
            ->buttons([])
            ->parameters([
                'dom' => "<'row'<'col-sm-6 margin-bottom20'B>>" .
                    "<'row'<'col-sm-6'l><'col-sm-6'f>>" .
                    "<'row'<'col-sm-12'tr>>" .
                    "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                'lengthMenu' => [50, 100, 200, 300, 500],
            ])
            ->language([
                'lengthMenu' => 'Show _MENU_ entries',
                'sProcessing' => __('Loading...'),
                'sZeroRecords' => __('There is no data'),
                'sEmptyTable' => __('There is no data'),
                'infoFiltered' => '',
                'sInfo' => '',
                'sInfoEmpty' => '',
                'sInfoPostFix' => '',
                'sSearch' => '',
                'sSearchPlaceholder' => __('Search'),
                'sUrl' => '',
                'sInfoThousands' => ',',
                'sLoadingRecords' => __('Loading...'),
                'oPaginate' => [
                    'sNext' => "<i class='next'></i>",
                    'sPrevious' => "<i class='previous'></i>",
                ]
            ]);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DataTables\CategoryDataTable, whose contract is defined in another file
**Source:** `app/DataTables/CategoryDataTable.php` :: `query` (lines 59-66)
**Tokens:** 60

```php
    /**
     * Get the query source of dataTable.
     */
    public function query(Category $model): QueryBuilder
    {
        return $model->newQuery()->mainCategory()->with("subCategories")
        ->orderBy("position" , "ASC");
    }
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/SubCategoryController.php` :: `App\Models\Category::where` (lines 29-41)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `activeSubCategories` (lines 36-39)
**Tokens:** 38

```php
    public function activeSubCategories()
    {
        return $this->hasMany(self::class)->orderBy('position', "ASC")->active()->international();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `category` (lines 28-31)
**Tokens:** 22

```php
    public function category()
    {
        return $this->belongsTo(self::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `fillable` (lines 17-25)
**Tokens:** 44

```php
    protected $fillable = [
        'name',
        'image',
        'is_international',
        'home',
        'is_active',
        'category_id',
        "position"
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `scopeCategory` (lines 50-53)
**Tokens:** 31

```php
    public function scopeCategory($query)
    {
        return $query->where('category_id', request()->category_id);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `scopeHome` (lines 41-44)
**Tokens:** 23

```php
    public function scopeHome($query)
    {
        return $query->where('home', 1);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::mainCategory, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `scopeMainCategory` (lines 46-49)
**Tokens:** 27

```php
    public function scopeMainCategory($query)
    {
        return $query->whereNull('category_id');
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `scopeMainCategory` (lines 46-49)
**Tokens:** 27

```php
    public function scopeMainCategory($query)
    {
        return $query->whereNull('category_id');
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `subCategories` (lines 32-35)
**Tokens:** 23

```php
    public function subCategories()
    {
        return $this->hasMany(self::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `translatable` (lines 16-16)
**Tokens:** 9

```php
    public $translatable = ['name'];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::active, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeActive` (lines 121-124)
**Tokens:** 25

```php
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::filter, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilter` (lines 145-156)
**Tokens:** 73

```php
    public function scopeFilter(Builder $builder)
    {

        $builder
            ->filterByStatus()
            ->filterById()
            ->filterByName()
            ->filterByCategories()
            ->filterBySku()
            ->filterByQuantity()
            ->filterByDate();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::inactive, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeInactive` (lines 116-119)
**Tokens:** 25

```php
    public function scopeInactive($query)
    {
        return $query->where('is_active', 0);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Services\CategoryService::update, whose contract is defined in another file
**Source:** `app/Services/CategoryService.php` :: `update` (lines 31-34)
**Tokens:** 28

```php
    public function update($model ,$request)
    {
        return $this->repo->update($model , $request);
    }
```

## Dropped

Nothing was dropped.
