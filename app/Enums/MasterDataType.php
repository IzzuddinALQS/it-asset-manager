<?php

namespace App\Enums;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

enum MasterDataType: string
{
    case Categories = 'categories';
    case Manufacturers = 'manufacturers';
    case Suppliers = 'suppliers';
    case Locations = 'locations';
    case Models = 'models';

    public function label(): string
    {
        return match ($this) {
            self::Categories => 'Kategori',
            self::Manufacturers => 'Manufaktur',
            self::Suppliers => 'Supplier',
            self::Locations => 'Lokasi',
            self::Models => 'Model inventaris',
        };
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Categories => Category::class,
            self::Manufacturers => Manufacturer::class,
            self::Suppliers => Supplier::class,
            self::Locations => Location::class,
            self::Models => AssetModel::class,
        };
    }

    /**
     * Form fields in display order; `options` names a select option list sent with the page.
     *
     * @return list<array{key: string, label: string, input: 'text'|'email'|'number'|'textarea'|'select'|'checkbox', required: bool, options?: string}>
     */
    public function fields(): array
    {
        return match ($this) {
            self::Categories => [
                ['key' => 'name', 'label' => 'Nama', 'input' => 'text', 'required' => true],
                ['key' => 'type', 'label' => 'Jenis', 'input' => 'select', 'required' => true, 'options' => 'categoryTypes'],
                ['key' => 'tag_prefix', 'label' => 'Prefix tag', 'input' => 'text', 'required' => false],
            ],
            self::Manufacturers => [
                ['key' => 'name', 'label' => 'Nama', 'input' => 'text', 'required' => true],
            ],
            self::Suppliers => [
                ['key' => 'name', 'label' => 'Nama', 'input' => 'text', 'required' => true],
                ['key' => 'contact_name', 'label' => 'Kontak', 'input' => 'text', 'required' => false],
                ['key' => 'phone', 'label' => 'Telepon', 'input' => 'text', 'required' => false],
                ['key' => 'email', 'label' => 'Email', 'input' => 'email', 'required' => false],
                ['key' => 'is_service_vendor', 'label' => 'Vendor servis', 'input' => 'checkbox', 'required' => false],
            ],
            self::Locations => [
                ['key' => 'name', 'label' => 'Nama', 'input' => 'text', 'required' => true],
                ['key' => 'address', 'label' => 'Alamat', 'input' => 'textarea', 'required' => false],
            ],
            self::Models => [
                ['key' => 'name', 'label' => 'Nama', 'input' => 'text', 'required' => true],
                ['key' => 'model_number', 'label' => 'Nomor model', 'input' => 'text', 'required' => false],
                ['key' => 'category_id', 'label' => 'Kategori', 'input' => 'select', 'required' => true, 'options' => 'assetCategories'],
                ['key' => 'manufacturer_id', 'label' => 'Manufaktur', 'input' => 'select', 'required' => false, 'options' => 'manufacturers'],
                ['key' => 'depreciation_months', 'label' => 'Umur depresiasi (bulan)', 'input' => 'number', 'required' => false],
            ],
        };
    }
}
