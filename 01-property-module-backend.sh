#!/bin/bash
# ============================================
# 01-property-module-backend.sh
# نسخة الماك من سكربت بناء موديول العقارات:
# Property, Floor, Unit, Tenant + صلاحيات جديدة
# ============================================
set -e

ROOT="$HOME/Documents/GitHub/invoice-system"

write_file() {
  local path="$1"
  mkdir -p "$(dirname "$path")"
  if [ -f "$path" ]; then
    cp "$path" "$path.bak"
    echo "نسخة احتياطية: $path.bak"
  fi
  cat > "$path"
  echo "تم: $path"
}

# ════════════════════════════════════════════
# 1) MIGRATIONS
# ════════════════════════════════════════════
TS_PROPERTIES=$(date +%Y_%m_%d_%H%M%S); sleep 1
TS_FLOORS=$(date +%Y_%m_%d_%H%M%S); sleep 1
TS_UNITS=$(date +%Y_%m_%d_%H%M%S); sleep 1
TS_TENANTS=$(date +%Y_%m_%d_%H%M%S)

write_file "$ROOT/database/migrations/${TS_PROPERTIES}_create_properties_table.php" <<'PHP_EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
PHP_EOF

write_file "$ROOT/database/migrations/${TS_FLOORS}_create_floors_table.php" <<'PHP_EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->integer('floor_number');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['property_id', 'floor_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floors');
    }
};
PHP_EOF

write_file "$ROOT/database/migrations/${TS_UNITS}_create_units_table.php" <<'PHP_EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained('floors')->onDelete('cascade');
            $table->string('name');
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('monthly_rent', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['floor_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
PHP_EOF

write_file "$ROOT/database/migrations/${TS_TENANTS}_create_tenants_table.php" <<'PHP_EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->unique()->constrained('units')->onDelete('set null');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
PHP_EOF

# ════════════════════════════════════════════
# 2) PROPERTY
# ════════════════════════════════════════════
write_file "$ROOT/app/Models/Property.php" <<'PHP_EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'name_en', 'address', 'city', 'phone', 'email',
        'notes', 'is_active', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function floors()
    {
        return $this->hasMany(Floor::class);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('name_en', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('address', 'like', "%{$term}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
PHP_EOF

write_file "$ROOT/app/Repository/Admin/Property/PropertyInterface.php" <<'PHP_EOF'
<?php

namespace App\Repository\Admin\Property;

interface PropertyInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $property);
    public function destroy($property);
}
PHP_EOF

write_file "$ROOT/app/Repository/Admin/Property/PropertyRepository.php" <<'PHP_EOF'
<?php

namespace App\Repository\Admin\Property;

use App\Models\Property;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyRepository implements PropertyInterface
{
    public function index($request)
    {
        try {
            $query = Property::withCount('floors')
                ->with('creator:id,name')
                ->when($request->attributes->get('selected_branch_id'), fn($q, $branchId) => $q->where('branch_id', $branchId))
                ->orderBy('created_at', 'desc');

            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', (bool) $request->is_active);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = substr(trim($request->search), 0, 100);
                $query->search($search);
            }

            if ($request->has('per_page')) {
                $perPage = (int) $request->per_page;
                $perPage = min($perPage, Constants::MAX_PER_PAGE);
                $perPage = max($perPage, Constants::MIN_PER_PAGE);
                $properties = $query->paginate($perPage);
            } else {
                $properties = $query->paginate(Constants::DEFAULT_PER_PAGE);
            }

            return ['status' => true, 'message' => __('messages.properties_fetched'), 'data' => $properties];
        } catch (\Exception $e) {
            Log::error('PropertyRepository index error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id) || (int) $id <= 0) {
                return ['status' => false, 'message' => __('messages.property_not_found'), 'data' => null];
            }

            $property = Property::withCount('floors')->with('creator:id,name', 'branch:id,name')->find((int) $id);

            if (!$property) {
                return ['status' => false, 'message' => __('messages.property_not_found'), 'data' => null];
            }

            return ['status' => true, 'message' => __('messages.property_fetched'), 'data' => $property];
        } catch (\Exception $e) {
            Log::error('PropertyRepository show error', ['id' => $id, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            $branchId = $request->has('branch_id') && $request->branch_id
                ? (int) $request->branch_id
                : $request->attributes->get('selected_branch_id');

            $allowedBranchIds = $request->attributes->get('allowed_branch_ids');
            if ($branchId && is_array($allowedBranchIds) && !in_array((int) $branchId, $allowedBranchIds, true)) {
                DB::rollBack();
                return ['status' => false, 'message' => 'ليس لديك صلاحية لإضافة عقار لهذا الفرع', 'data' => null];
            }

            $property = Property::create([
                'name'       => $request->name,
                'name_en'    => $request->name_en,
                'address'    => $request->address,
                'city'       => $request->city,
                'phone'      => $request->phone,
                'email'      => $request->email,
                'notes'      => $request->notes,
                'is_active'  => $request->is_active ?? true,
                'branch_id'  => $branchId ? (int) $branchId : null,
                'created_by' => auth()->id(),
            ]);

            ActivityLog::log('CREATE', __('messages.property_created') . ': ' . $property->name, $property);

            DB::commit();

            return ['status' => true, 'message' => __('messages.property_created'), 'data' => $property->load('creator:id,name')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PropertyRepository store error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function update($request, $property)
    {
        DB::beginTransaction();

        try {
            $oldValues = $property->toArray();

            $allowedFields = ['name', 'name_en', 'address', 'city', 'phone', 'email', 'notes', 'is_active', 'branch_id'];

            $updateData = [];
            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            if (isset($updateData['is_active'])) {
                $updateData['is_active'] = (bool) $updateData['is_active'];
            }

            if (array_key_exists('branch_id', $updateData)) {
                $newBranchId = $updateData['branch_id'] ? (int) $updateData['branch_id'] : null;
                $allowedBranchIds = $request->attributes->get('allowed_branch_ids');

                if ($newBranchId && is_array($allowedBranchIds) && !in_array($newBranchId, $allowedBranchIds, true)) {
                    DB::rollBack();
                    return ['status' => false, 'message' => 'ليس لديك صلاحية لنقل العقار لهذا الفرع', 'data' => null];
                }

                $updateData['branch_id'] = $newBranchId;
            }

            $property->update($updateData);

            ActivityLog::log('UPDATE', __('messages.property_updated') . ': ' . $property->name, $property, $oldValues, $property->fresh()->toArray());

            DB::commit();

            return ['status' => true, 'message' => __('messages.property_updated'), 'data' => $property->fresh()->load('creator:id,name')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PropertyRepository update error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function destroy($property)
    {
        DB::beginTransaction();

        try {
            $property->loadCount('floors');

            if ($property->floors_count > 0) {
                DB::rollBack();
                return ['status' => false, 'message' => __('messages.property_has_floors'), 'data' => null];
            }

            $propertyName = $property->name;
            $propertyId   = $property->id;

            ActivityLog::log('DELETE', __('messages.property_deleted') . ': ' . $propertyName, $property);
            $property->delete();

            DB::commit();

            return ['status' => true, 'message' => __('messages.property_deleted'), 'data' => ['id' => $propertyId]];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PropertyRepository destroy error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Requests/Admin/Property/StorePropertyRequest.php" <<'PHP_EOF'
<?php

namespace App\Http\Requests\Admin\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'name_en'   => ['nullable', 'string', 'max:255'],
            'address'   => ['nullable', 'string'],
            'city'      => ['nullable', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:255'],
            'notes'     => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Requests/Admin/Property/UpdatePropertyRequest.php" <<'PHP_EOF'
<?php

namespace App\Http\Requests\Admin\Property;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'name_en'   => ['nullable', 'string', 'max:255'],
            'address'   => ['nullable', 'string'],
            'city'      => ['nullable', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:255'],
            'notes'     => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Controllers/Admin/PropertyController.php" <<'PHP_EOF'
<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Property\PropertyInterface;
use App\Http\Requests\Admin\Property\StorePropertyRequest;
use App\Http\Requests\Admin\Property\UpdatePropertyRequest;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    use ResponseTrait;

    protected $property;

    public function __construct(PropertyInterface $property)
    {
        $this->property = $property;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_PROPERTIES)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->index($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.properties_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_PROPERTIES)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->show($id);

        if ($data['status']) {
            return $this->successResponse(__('messages.property_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StorePropertyRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_PROPERTY)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->store($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.property_created'), $data['data'], Constants::RESPONSE_CREATED);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdatePropertyRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_PROPERTY)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->property->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(__('messages.property_updated'), $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_PROPERTY)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->property->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(__('messages.property_deleted'), $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }
}
PHP_EOF

write_file "$ROOT/routes/admin/properties.php" <<'PHP_EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PropertyController;

Route::group(['prefix' => 'properties'], function () {
    Route::get('/', [PropertyController::class, 'index']);
    Route::post('/', [PropertyController::class, 'store']);
    Route::get('/{id}', [PropertyController::class, 'show']);
    Route::put('/{id}', [PropertyController::class, 'update']);
    Route::delete('/{id}', [PropertyController::class, 'destroy']);
});
PHP_EOF

# ════════════════════════════════════════════
# 3) FLOOR
# ════════════════════════════════════════════
write_file "$ROOT/app/Models/Floor.php" <<'PHP_EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'name', 'name_en', 'floor_number', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'floor_number' => 'integer',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
PHP_EOF

write_file "$ROOT/app/Repository/Admin/Floor/FloorInterface.php" <<'PHP_EOF'
<?php

namespace App\Repository\Admin\Floor;

interface FloorInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $floor);
    public function destroy($floor);
}
PHP_EOF

write_file "$ROOT/app/Repository/Admin/Floor/FloorRepository.php" <<'PHP_EOF'
<?php

namespace App\Repository\Admin\Floor;

use App\Models\Floor;
use App\Models\Property;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FloorRepository implements FloorInterface
{
    public function index($request)
    {
        try {
            $query = Floor::withCount('units')
                ->with('property:id,name')
                ->whereHas('property', function ($q) use ($request) {
                    $q->when($request->attributes->get('selected_branch_id'), fn($qq, $branchId) => $qq->where('branch_id', $branchId));
                })
                ->orderBy('floor_number');

            if ($request->has('property_id') && $request->property_id) {
                $query->where('property_id', (int) $request->property_id);
            }

            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', (bool) $request->is_active);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = substr(trim($request->search), 0, 100);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%");
                });
            }

            if ($request->has('per_page')) {
                $perPage = (int) $request->per_page;
                $perPage = min($perPage, Constants::MAX_PER_PAGE);
                $perPage = max($perPage, Constants::MIN_PER_PAGE);
                $floors = $query->paginate($perPage);
            } else {
                $floors = $query->paginate(Constants::DEFAULT_PER_PAGE);
            }

            return ['status' => true, 'message' => __('messages.floors_fetched'), 'data' => $floors];
        } catch (\Exception $e) {
            Log::error('FloorRepository index error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id) || (int) $id <= 0) {
                return ['status' => false, 'message' => __('messages.floor_not_found'), 'data' => null];
            }

            $floor = Floor::withCount('units')->with('property:id,name')->find((int) $id);

            if (!$floor) {
                return ['status' => false, 'message' => __('messages.floor_not_found'), 'data' => null];
            }

            return ['status' => true, 'message' => __('messages.floor_fetched'), 'data' => $floor];
        } catch (\Exception $e) {
            Log::error('FloorRepository show error', ['id' => $id, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            $property = Property::find((int) $request->property_id);

            if (!$property) {
                DB::rollBack();
                return ['status' => false, 'message' => __('messages.property_not_found'), 'data' => null];
            }

            $allowedBranchIds = $request->attributes->get('allowed_branch_ids');
            if ($property->branch_id && is_array($allowedBranchIds) && !in_array((int) $property->branch_id, $allowedBranchIds, true)) {
                DB::rollBack();
                return ['status' => false, 'message' => 'ليس لديك صلاحية على هذا العقار', 'data' => null];
            }

            $floor = Floor::create([
                'property_id'  => $property->id,
                'name'         => $request->name,
                'name_en'      => $request->name_en,
                'floor_number' => $request->floor_number,
                'description'  => $request->description,
                'is_active'    => $request->is_active ?? true,
                'created_by'   => auth()->id(),
            ]);

            ActivityLog::log('CREATE', __('messages.floor_created') . ': ' . $floor->name, $floor);

            DB::commit();

            return ['status' => true, 'message' => __('messages.floor_created'), 'data' => $floor->load('property:id,name')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FloorRepository store error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function update($request, $floor)
    {
        DB::beginTransaction();

        try {
            $oldValues = $floor->toArray();

            $allowedFields = ['name', 'name_en', 'floor_number', 'description', 'is_active'];

            $updateData = [];
            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            if (isset($updateData['is_active'])) {
                $updateData['is_active'] = (bool) $updateData['is_active'];
            }

            $floor->update($updateData);

            ActivityLog::log('UPDATE', __('messages.floor_updated') . ': ' . $floor->name, $floor, $oldValues, $floor->fresh()->toArray());

            DB::commit();

            return ['status' => true, 'message' => __('messages.floor_updated'), 'data' => $floor->fresh()->load('property:id,name')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FloorRepository update error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function destroy($floor)
    {
        DB::beginTransaction();

        try {
            $floor->loadCount('units');

            if ($floor->units_count > 0) {
                DB::rollBack();
                return ['status' => false, 'message' => __('messages.floor_has_units'), 'data' => null];
            }

            $floorName = $floor->name;
            $floorId   = $floor->id;

            ActivityLog::log('DELETE', __('messages.floor_deleted') . ': ' . $floorName, $floor);
            $floor->delete();

            DB::commit();

            return ['status' => true, 'message' => __('messages.floor_deleted'), 'data' => ['id' => $floorId]];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FloorRepository destroy error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Requests/Admin/Floor/StoreFloorRequest.php" <<'PHP_EOF'
<?php

namespace App\Http\Requests\Admin\Floor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id'  => ['required', 'exists:properties,id'],
            'name'         => ['required', 'string', 'max:255'],
            'name_en'      => ['nullable', 'string', 'max:255'],
            'floor_number' => [
                'required',
                'integer',
                Rule::unique('floors')->where(fn ($q) => $q->where('property_id', $this->property_id)),
            ],
            'description'  => ['nullable', 'string'],
            'is_active'    => ['boolean'],
        ];
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Requests/Admin/Floor/UpdateFloorRequest.php" <<'PHP_EOF'
<?php

namespace App\Http\Requests\Admin\Floor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $floorId = $this->route('id');

        return [
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'name_en'      => ['nullable', 'string', 'max:255'],
            'floor_number' => [
                'sometimes',
                'required',
                'integer',
                Rule::unique('floors')->where(fn ($q) => $q->where('property_id', $this->property_id))->ignore($floorId),
            ],
            'description'  => ['nullable', 'string'],
            'is_active'    => ['boolean'],
        ];
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Controllers/Admin/FloorController.php" <<'PHP_EOF'
<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Floor\FloorInterface;
use App\Http\Requests\Admin\Floor\StoreFloorRequest;
use App\Http\Requests\Admin\Floor\UpdateFloorRequest;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    use ResponseTrait;

    protected $floor;

    public function __construct(FloorInterface $floor)
    {
        $this->floor = $floor;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_FLOORS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->index($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.floors_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_FLOORS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->show($id);

        if ($data['status']) {
            return $this->successResponse(__('messages.floor_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreFloorRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_FLOOR)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->store($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.floor_created'), $data['data'], Constants::RESPONSE_CREATED);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateFloorRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_FLOOR)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->floor->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(__('messages.floor_updated'), $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_FLOOR)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->floor->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(__('messages.floor_deleted'), $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }
}
PHP_EOF

write_file "$ROOT/routes/admin/floors.php" <<'PHP_EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FloorController;

Route::group(['prefix' => 'floors'], function () {
    Route::get('/', [FloorController::class, 'index']);
    Route::post('/', [FloorController::class, 'store']);
    Route::get('/{id}', [FloorController::class, 'show']);
    Route::put('/{id}', [FloorController::class, 'update']);
    Route::delete('/{id}', [FloorController::class, 'destroy']);
});
PHP_EOF

# ════════════════════════════════════════════
# 4) UNIT
# ════════════════════════════════════════════
write_file "$ROOT/app/Models/Unit.php" <<'PHP_EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'floor_id', 'name', 'area', 'monthly_rent', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'area'         => 'decimal:2',
        'monthly_rent' => 'decimal:2',
    ];

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
PHP_EOF

write_file "$ROOT/app/Repository/Admin/Unit/UnitInterface.php" <<'PHP_EOF'
<?php

namespace App\Repository\Admin\Unit;

interface UnitInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $unit);
    public function destroy($unit);
}
PHP_EOF

write_file "$ROOT/app/Repository/Admin/Unit/UnitRepository.php" <<'PHP_EOF'
<?php

namespace App\Repository\Admin\Unit;

use App\Models\Unit;
use App\Models\Floor;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnitRepository implements UnitInterface
{
    public function index($request)
    {
        try {
            $query = Unit::with('floor:id,name,property_id', 'tenant:id,unit_id,name')
                ->whereHas('floor.property', function ($q) use ($request) {
                    $q->when($request->attributes->get('selected_branch_id'), fn($qq, $branchId) => $qq->where('branch_id', $branchId));
                })
                ->orderBy('name');

            if ($request->has('floor_id') && $request->floor_id) {
                $query->where('floor_id', (int) $request->floor_id);
            }

            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', (bool) $request->is_active);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = substr(trim($request->search), 0, 100);
                $query->where('name', 'like', "%{$search}%");
            }

            if ($request->has('per_page')) {
                $perPage = (int) $request->per_page;
                $perPage = min($perPage, Constants::MAX_PER_PAGE);
                $perPage = max($perPage, Constants::MIN_PER_PAGE);
                $units = $query->paginate($perPage);
            } else {
                $units = $query->paginate(Constants::DEFAULT_PER_PAGE);
            }

            return ['status' => true, 'message' => __('messages.units_fetched'), 'data' => $units];
        } catch (\Exception $e) {
            Log::error('UnitRepository index error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id) || (int) $id <= 0) {
                return ['status' => false, 'message' => __('messages.unit_not_found'), 'data' => null];
            }

            $unit = Unit::with('floor:id,name,property_id', 'tenant')->find((int) $id);

            if (!$unit) {
                return ['status' => false, 'message' => __('messages.unit_not_found'), 'data' => null];
            }

            return ['status' => true, 'message' => __('messages.unit_fetched'), 'data' => $unit];
        } catch (\Exception $e) {
            Log::error('UnitRepository show error', ['id' => $id, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            $floor = Floor::find((int) $request->floor_id);

            if (!$floor) {
                DB::rollBack();
                return ['status' => false, 'message' => __('messages.floor_not_found'), 'data' => null];
            }

            $unit = Unit::create([
                'floor_id'     => $floor->id,
                'name'         => $request->name,
                'area'         => $request->area,
                'monthly_rent' => $request->monthly_rent,
                'description'  => $request->description,
                'is_active'    => $request->is_active ?? true,
                'created_by'   => auth()->id(),
            ]);

            ActivityLog::log('CREATE', __('messages.unit_created') . ': ' . $unit->name, $unit);

            DB::commit();

            return ['status' => true, 'message' => __('messages.unit_created'), 'data' => $unit->load('floor:id,name')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UnitRepository store error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function update($request, $unit)
    {
        DB::beginTransaction();

        try {
            $oldValues = $unit->toArray();

            $allowedFields = ['name', 'area', 'monthly_rent', 'description', 'is_active'];

            $updateData = [];
            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            if (isset($updateData['is_active'])) {
                $updateData['is_active'] = (bool) $updateData['is_active'];
            }

            $unit->update($updateData);

            ActivityLog::log('UPDATE', __('messages.unit_updated') . ': ' . $unit->name, $unit, $oldValues, $unit->fresh()->toArray());

            DB::commit();

            return ['status' => true, 'message' => __('messages.unit_updated'), 'data' => $unit->fresh()->load('floor:id,name')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UnitRepository update error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function destroy($unit)
    {
        DB::beginTransaction();

        try {
            $unit->loadCount('tenant');

            if ($unit->tenant_count > 0) {
                DB::rollBack();
                return ['status' => false, 'message' => __('messages.unit_has_tenant'), 'data' => null];
            }

            $unitName = $unit->name;
            $unitId   = $unit->id;

            ActivityLog::log('DELETE', __('messages.unit_deleted') . ': ' . $unitName, $unit);
            $unit->delete();

            DB::commit();

            return ['status' => true, 'message' => __('messages.unit_deleted'), 'data' => ['id' => $unitId]];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UnitRepository destroy error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Requests/Admin/Unit/StoreUnitRequest.php" <<'PHP_EOF'
<?php

namespace App\Http\Requests\Admin\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'floor_id'     => ['required', 'exists:floors,id'],
            'name'         => [
                'required', 'string', 'max:255',
                Rule::unique('units')->where(fn ($q) => $q->where('floor_id', $this->floor_id)),
            ],
            'area'         => ['nullable', 'numeric', 'min:0'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string'],
            'is_active'    => ['boolean'],
        ];
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Requests/Admin/Unit/UpdateUnitRequest.php" <<'PHP_EOF'
<?php

namespace App\Http\Requests\Admin\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('id');

        return [
            'name'         => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('units')->where(fn ($q) => $q->where('floor_id', $this->floor_id))->ignore($unitId),
            ],
            'area'         => ['nullable', 'numeric', 'min:0'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string'],
            'is_active'    => ['boolean'],
        ];
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Controllers/Admin/UnitController.php" <<'PHP_EOF'
<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Unit\UnitInterface;
use App\Http\Requests\Admin\Unit\StoreUnitRequest;
use App\Http\Requests\Admin\Unit\UpdateUnitRequest;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use ResponseTrait;

    protected $unit;

    public function __construct(UnitInterface $unit)
    {
        $this->unit = $unit;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_UNITS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->index($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.units_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_UNITS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->show($id);

        if ($data['status']) {
            return $this->successResponse(__('messages.unit_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreUnitRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_UNIT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->store($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.unit_created'), $data['data'], Constants::RESPONSE_CREATED);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateUnitRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_UNIT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->unit->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(__('messages.unit_updated'), $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_UNIT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->unit->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(__('messages.unit_deleted'), $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }
}
PHP_EOF

write_file "$ROOT/routes/admin/units.php" <<'PHP_EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UnitController;

Route::group(['prefix' => 'units'], function () {
    Route::get('/', [UnitController::class, 'index']);
    Route::post('/', [UnitController::class, 'store']);
    Route::get('/{id}', [UnitController::class, 'show']);
    Route::put('/{id}', [UnitController::class, 'update']);
    Route::delete('/{id}', [UnitController::class, 'destroy']);
});
PHP_EOF

# ════════════════════════════════════════════
# 5) TENANT
# ════════════════════════════════════════════
write_file "$ROOT/app/Models/Tenant.php" <<'PHP_EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id', 'name', 'email', 'phone', 'id_number', 'notes', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
PHP_EOF

write_file "$ROOT/app/Repository/Admin/Tenant/TenantInterface.php" <<'PHP_EOF'
<?php

namespace App\Repository\Admin\Tenant;

interface TenantInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $tenant);
    public function destroy($tenant);
}
PHP_EOF

write_file "$ROOT/app/Repository/Admin/Tenant/TenantRepository.php" <<'PHP_EOF'
<?php

namespace App\Repository\Admin\Tenant;

use App\Models\Tenant;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantRepository implements TenantInterface
{
    public function index($request)
    {
        try {
            $query = Tenant::with('unit:id,name,floor_id')
                ->whereHas('unit.floor.property', function ($q) use ($request) {
                    $q->when($request->attributes->get('selected_branch_id'), fn($qq, $branchId) => $qq->where('branch_id', $branchId));
                }, '>=', 0)
                ->orWhereNull('unit_id')
                ->orderBy('created_at', 'desc');

            if ($request->has('unit_id') && $request->unit_id) {
                $query->where('unit_id', (int) $request->unit_id);
            }

            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', (bool) $request->is_active);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = substr(trim($request->search), 0, 100);
                $query->search($search);
            }

            if ($request->has('per_page')) {
                $perPage = (int) $request->per_page;
                $perPage = min($perPage, Constants::MAX_PER_PAGE);
                $perPage = max($perPage, Constants::MIN_PER_PAGE);
                $tenants = $query->paginate($perPage);
            } else {
                $tenants = $query->paginate(Constants::DEFAULT_PER_PAGE);
            }

            return ['status' => true, 'message' => __('messages.tenants_fetched'), 'data' => $tenants];
        } catch (\Exception $e) {
            Log::error('TenantRepository index error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id) || (int) $id <= 0) {
                return ['status' => false, 'message' => __('messages.tenant_not_found'), 'data' => null];
            }

            $tenant = Tenant::with('unit:id,name,floor_id')->find((int) $id);

            if (!$tenant) {
                return ['status' => false, 'message' => __('messages.tenant_not_found'), 'data' => null];
            }

            return ['status' => true, 'message' => __('messages.tenant_fetched'), 'data' => $tenant];
        } catch (\Exception $e) {
            Log::error('TenantRepository show error', ['id' => $id, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            $unitId = $request->unit_id ? (int) $request->unit_id : null;

            if ($unitId && Tenant::where('unit_id', $unitId)->exists()) {
                DB::rollBack();
                return ['status' => false, 'message' => __('messages.unit_already_taken'), 'data' => null];
            }

            $tenant = Tenant::create([
                'unit_id'    => $unitId,
                'name'       => $request->name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'id_number'  => $request->id_number,
                'notes'      => $request->notes,
                'is_active'  => $request->is_active ?? true,
                'created_by' => auth()->id(),
            ]);

            ActivityLog::log('CREATE', __('messages.tenant_created') . ': ' . $tenant->name, $tenant);

            DB::commit();

            return ['status' => true, 'message' => __('messages.tenant_created'), 'data' => $tenant->load('unit:id,name')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TenantRepository store error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function update($request, $tenant)
    {
        DB::beginTransaction();

        try {
            $oldValues = $tenant->toArray();

            if ($request->has('unit_id')) {
                $newUnitId = $request->unit_id ? (int) $request->unit_id : null;

                if ($newUnitId && $newUnitId !== $tenant->unit_id && Tenant::where('unit_id', $newUnitId)->exists()) {
                    DB::rollBack();
                    return ['status' => false, 'message' => __('messages.unit_already_taken'), 'data' => null];
                }
            }

            $allowedFields = ['unit_id', 'name', 'email', 'phone', 'id_number', 'notes', 'is_active'];

            $updateData = [];
            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            if (isset($updateData['is_active'])) {
                $updateData['is_active'] = (bool) $updateData['is_active'];
            }
            if (array_key_exists('unit_id', $updateData)) {
                $updateData['unit_id'] = $updateData['unit_id'] ? (int) $updateData['unit_id'] : null;
            }

            $tenant->update($updateData);

            ActivityLog::log('UPDATE', __('messages.tenant_updated') . ': ' . $tenant->name, $tenant, $oldValues, $tenant->fresh()->toArray());

            DB::commit();

            return ['status' => true, 'message' => __('messages.tenant_updated'), 'data' => $tenant->fresh()->load('unit:id,name')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TenantRepository update error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }

    public function destroy($tenant)
    {
        DB::beginTransaction();

        try {
            $tenantName = $tenant->name;
            $tenantId   = $tenant->id;

            ActivityLog::log('DELETE', __('messages.tenant_deleted') . ': ' . $tenantName, $tenant);
            $tenant->delete();

            DB::commit();

            return ['status' => true, 'message' => __('messages.tenant_deleted'), 'data' => ['id' => $tenantId]];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TenantRepository destroy error', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.operation_failed'), 'data' => null];
        }
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Requests/Admin/Tenant/StoreTenantRequest.php" <<'PHP_EOF'
<?php

namespace App\Http\Requests\Admin\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id'   => ['nullable', 'exists:units,id'],
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['nullable', 'email', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'notes'     => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Requests/Admin/Tenant/UpdateTenantRequest.php" <<'PHP_EOF'
<?php

namespace App\Http\Requests\Admin\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id'   => ['nullable', 'exists:units,id'],
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['nullable', 'email', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'notes'     => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
PHP_EOF

write_file "$ROOT/app/Http/Controllers/Admin/TenantController.php" <<'PHP_EOF'
<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Tenant\TenantInterface;
use App\Http\Requests\Admin\Tenant\StoreTenantRequest;
use App\Http\Requests\Admin\Tenant\UpdateTenantRequest;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use ResponseTrait;

    protected $tenant;

    public function __construct(TenantInterface $tenant)
    {
        $this->tenant = $tenant;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_TENANTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->index($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.tenants_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_TENANTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->show($id);

        if ($data['status']) {
            return $this->successResponse(__('messages.tenant_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreTenantRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_TENANT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->store($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.tenant_created'), $data['data'], Constants::RESPONSE_CREATED);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateTenantRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_TENANT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->tenant->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(__('messages.tenant_updated'), $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_TENANT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->tenant->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(__('messages.tenant_deleted'), $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }
}
PHP_EOF

write_file "$ROOT/routes/admin/tenants.php" <<'PHP_EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TenantController;

Route::group(['prefix' => 'tenants'], function () {
    Route::get('/', [TenantController::class, 'index']);
    Route::post('/', [TenantController::class, 'store']);
    Route::get('/{id}', [TenantController::class, 'show']);
    Route::put('/{id}', [TenantController::class, 'update']);
    Route::delete('/{id}', [TenantController::class, 'destroy']);
});
PHP_EOF

# ════════════════════════════════════════════
# 6) Constants.php
# ════════════════════════════════════════════
write_file "$ROOT/app/Constants/Constants.php" <<'PHP_EOF'
<?php

namespace App\Constants;

class Constants
{
    const ACTIVE = 1;
    const INACTIVE = 0;
    const YES = 1;
    const NO = 0;

    const SUPER_ADMIN_GROUP_ID = 1;
    const ADMIN_GROUP_ID = 2;
    const ACCOUNTANT_GROUP_ID = 3;
    const SALES_GROUP_ID = 4;
    const CLIENT_GROUP_ID = 5;

    const VIEW_DASHBOARD = 'view_dashboard';
    const VIEW_INVOICES = 'view_invoices';
    const CREATE_INVOICE = 'create_invoice';
    const EDIT_INVOICE = 'edit_invoice';
    const DELETE_INVOICE = 'delete_invoice';
    const CREATE_INSTALLMENT = 'create_installment';
    const SEND_INVOICE = 'send_invoice';
    const DOWNLOAD_INVOICE = 'download_invoice';
    const VIEW_CLIENTS = 'view_clients';
    const CREATE_CLIENT = 'create_client';
    const EDIT_CLIENT = 'edit_client';
    const DELETE_CLIENT = 'delete_client';
    const MANAGE_BRANCHES = 'manage_branches';
    const VIEW_USERS = 'view_users';
    const CREATE_USER = 'create_user';
    const EDIT_USER = 'edit_user';
    const DELETE_USER = 'delete_user';
    const VIEW_ADMIN_GROUPS = 'view_admin_groups';
    const MANAGE_ADMIN_GROUPS = 'manage_admin_groups';
    const VIEW_REPORTS = 'view_reports';
    const EXPORT_REPORTS = 'export_reports';
    const MANAGE_PERMISSIONS = 'manage_permissions';
    const MANAGE_USER_GROUPS = 'manage_user_groups';
    const VIEW_PERMISSIONS = 'view_permissions';
    const RESPONSE_TOO_MANY_REQUESTS = 429;

    // Pagination
    const DEFAULT_PER_PAGE = 10;
    const MAX_PER_PAGE = 100;
    const MIN_PER_PAGE = 1;

    // Invoice Statuses
    const INVOICE_STATUS_DRAFT = 'draft';
    const INVOICE_STATUS_SENT = 'sent';
    const INVOICE_STATUS_PAID = 'paid';
    const INVOICE_STATUS_OVERDUE = 'overdue';
    const INVOICE_STATUS_CANCELLED = 'cancelled';

    // Invoice Payment Terms
    const PAYMENT_TERM_NET_7 = 'net_7';
    const PAYMENT_TERM_NET_15 = 'net_15';
    const PAYMENT_TERM_NET_30 = 'net_30';
    const PAYMENT_TERM_NET_60 = 'net_60';

    // Currency
    const CURRENCY_SAR = 'SAR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_GBP = 'GBP';
    const CURRENCY_AED = 'AED';

    // Response Status Codes
    const RESPONSE_SUCCESS = 200;
    const RESPONSE_CREATED = 201;
    const RESPONSE_ACCEPTED = 202;
    const RESPONSE_NO_CONTENT = 204;
    const RESPONSE_BAD_REQUEST = 400;
    const RESPONSE_UNAUTHORIZED = 401;
    const RESPONSE_FORBIDDEN = 403;
    const RESPONSE_NOT_FOUND = 404;
    const RESPONSE_METHOD_NOT_ALLOWED = 405;
    const RESPONSE_VALIDATION_ERROR = 422;
    const RESPONSE_SERVER_ERROR = 500;

    // Activity Types
    const ACTIVITY_CREATE = 'CREATE';
    const ACTIVITY_UPDATE = 'UPDATE';
    const ACTIVITY_DELETE = 'DELETE';
    const ACTIVITY_LOGIN = 'LOGIN';
    const ACTIVITY_LOGOUT = 'LOGOUT';


    // Cache Times (in seconds)
    const CACHE_5_MINUTES = 300;
    const CACHE_10_MINUTES = 600;
    const CACHE_30_MINUTES = 1800;
    const CACHE_1_HOUR = 3600;
    const CACHE_1_DAY = 86400;

    // Date Formats
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const TIME_FORMAT = 'H:i:s';

    // File Upload
    const MAX_FILE_SIZE = 2048; // 2MB in KB
    const ALLOWED_FILE_TYPES = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

    // Validation Rules
    const PASSWORD_MIN_LENGTH = 8;
    const PHONE_MAX_LENGTH = 20;
    const NAME_MAX_LENGTH = 255;
    const EMAIL_MAX_LENGTH = 255;

    // System Settings
    const SYSTEM_NAME_EN = 'Invoice System';
    const SYSTEM_NAME_AR = 'نظام الفواتير';
    const SYSTEM_VERSION = '1.0.0';

    // Notification Types
    const NOTIFICATION_INVOICE_CREATED = 'invoice_created';
    const NOTIFICATION_INVOICE_PAID = 'invoice_paid';
    const NOTIFICATION_INVOICE_OVERDUE = 'invoice_overdue';
    const NOTIFICATION_CLIENT_CREATED = 'client_created';

    // Report Types
    const REPORT_INVOICES = 'invoices';
    const REPORT_CLIENTS = 'clients';
    const REPORT_REVENUE = 'revenue';
    const REPORT_PAYMENTS = 'payments';

    // Export Formats
    const EXPORT_PDF = 'pdf';
    const EXPORT_EXCEL = 'excel';
    const EXPORT_CSV = 'csv';
    const CACHE_TTL_INVOICES = 600; // 10 minutes

    // Payment Statuses (بجانب Invoice Statuses)
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_COMPLETED = 'completed';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    // Payment Methods
    const PAYMENT_METHOD_CARD = 'card';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_CASH = 'cash';

    // Permissions (بجانب الصلاحيات الحالية)
    const CREATE_PAYMENT = 'create_payment';
    const VIEW_PAYMENTS = 'view_payments';
    const REFUND_PAYMENT = 'refund_payment';
    const VIEW_OTP_LOGS = 'view_otp_logs';
    const VIEW_SUPPORT_TICKETS = 'view_support_tickets';
    const REPLY_SUPPORT_TICKET = 'reply_support_ticket';
    const EDIT_SUPPORT_TICKET_STATUS = 'edit_support_ticket_status';
    const DELETE_SUPPORT_TICKET = 'delete_support_ticket';
    const REFUND_PAYMENTS = 'refund_payments';

    // Response Messages (أضف في قسم messages)
    const MESSAGE_PAYMENT_SESSION_CREATED = 'payment_session_created';
    const MESSAGE_PAYMENT_SUCCESS = 'payment_success';
    const MESSAGE_PAYMENT_FAILED = 'payment_failed';
    const MESSAGE_PAYMENT_CANCELLED = 'payment_cancelled';


    const INVOICE_STATUS_UNPAID = 'unpaid';
    const INVOICE_STATUS_PENDING = 'pending';

    // ═══════════════════════════════════════════════════════════════
    // Property & Facility Management Module
    // ═══════════════════════════════════════════════════════════════
    const VIEW_PROPERTIES = 'view_properties';
    const CREATE_PROPERTY = 'create_property';
    const EDIT_PROPERTY = 'edit_property';
    const DELETE_PROPERTY = 'delete_property';

    const VIEW_FLOORS = 'view_floors';
    const CREATE_FLOOR = 'create_floor';
    const EDIT_FLOOR = 'edit_floor';
    const DELETE_FLOOR = 'delete_floor';

    const VIEW_UNITS = 'view_units';
    const CREATE_UNIT = 'create_unit';
    const EDIT_UNIT = 'edit_unit';
    const DELETE_UNIT = 'delete_unit';

    const VIEW_TENANTS = 'view_tenants';
    const CREATE_TENANT = 'create_tenant';
    const EDIT_TENANT = 'edit_tenant';
    const DELETE_TENANT = 'delete_tenant';
}
PHP_EOF

# ════════════════════════════════════════════
# 7) Seeder إضافي فقط
# ════════════════════════════════════════════
write_file "$ROOT/database/seeders/PropertyModulePermissionsSeeder.php" <<'PHP_EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyModulePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admin_menus')->insertOrIgnore([
            'id'         => 7,
            'title_en'   => 'Properties',
            'title_ar'   => 'العقارات',
            'link'       => null,
            'icon_class' => 'fa-building',
            'sort_order' => 7,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = [
            ['id' => 27, 'title' => 'view_properties', 'description_en' => 'View Properties', 'description_ar' => 'عرض العقارات'],
            ['id' => 28, 'title' => 'create_property',  'description_en' => 'Create Property',  'description_ar' => 'إنشاء عقار'],
            ['id' => 29, 'title' => 'edit_property',    'description_en' => 'Edit Property',    'description_ar' => 'تعديل عقار'],
            ['id' => 30, 'title' => 'delete_property',  'description_en' => 'Delete Property',  'description_ar' => 'حذف عقار'],

            ['id' => 31, 'title' => 'view_floors',  'description_en' => 'View Floors',  'description_ar' => 'عرض الطوابق'],
            ['id' => 32, 'title' => 'create_floor', 'description_en' => 'Create Floor', 'description_ar' => 'إنشاء طابق'],
            ['id' => 33, 'title' => 'edit_floor',   'description_en' => 'Edit Floor',   'description_ar' => 'تعديل طابق'],
            ['id' => 34, 'title' => 'delete_floor', 'description_en' => 'Delete Floor', 'description_ar' => 'حذف طابق'],

            ['id' => 35, 'title' => 'view_units',  'description_en' => 'View Units',  'description_ar' => 'عرض الوحدات'],
            ['id' => 36, 'title' => 'create_unit', 'description_en' => 'Create Unit', 'description_ar' => 'إنشاء وحدة'],
            ['id' => 37, 'title' => 'edit_unit',   'description_en' => 'Edit Unit',   'description_ar' => 'تعديل وحدة'],
            ['id' => 38, 'title' => 'delete_unit', 'description_en' => 'Delete Unit', 'description_ar' => 'حذف وحدة'],

            ['id' => 39, 'title' => 'view_tenants',  'description_en' => 'View Tenants',  'description_ar' => 'عرض المستأجرين'],
            ['id' => 40, 'title' => 'create_tenant', 'description_en' => 'Create Tenant', 'description_ar' => 'إنشاء مستأجر'],
            ['id' => 41, 'title' => 'edit_tenant',   'description_en' => 'Edit Tenant',   'description_ar' => 'تعديل مستأجر'],
            ['id' => 42, 'title' => 'delete_tenant', 'description_en' => 'Delete Tenant', 'description_ar' => 'حذف مستأجر'],
        ];

        foreach ($permissions as $permission) {
            DB::table('admin_permissions')->insertOrIgnore(array_merge($permission, [
                'admin_menu_id'     => 7,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]));
        }

        foreach ([1, 2] as $groupId) {
            foreach ($permissions as $permission) {
                DB::table('admin_group_permissions')->insertOrIgnore([
                    'admin_group_id'      => $groupId,
                    'admin_permission_id' => $permission['id'],
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        }
    }
}
PHP_EOF

echo ""
echo "=== انتهى بناء الموديول بالكامل (Property/Floor/Unit/Tenant) ==="
echo "باقي الخطوات اليدوية قبل التشغيل (تحت الرسالة بالشات)."
