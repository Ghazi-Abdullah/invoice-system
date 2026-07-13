<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallmentInterestTier;
use App\Repository\Admin\InstallmentInterestTier\InstallmentInterestTierInterface;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class InstallmentInterestTierController extends Controller
{
    use ResponseTrait;

    protected InstallmentInterestTierInterface $tierRepository;

    public function __construct(InstallmentInterestTierInterface $tierRepository)
    {
        $this->tierRepository = $tierRepository;
    }

    public function index()
    {
        return $this->successResponse('تم جلب جدول نسب الفائدة', $this->tierRepository->all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number_of_installments' => 'required|integer|min:2|max:60',
            'interest_rate'          => 'required|numeric|min:0|max:100',
        ]);

        $tier = $this->tierRepository->upsert(
            $validated['number_of_installments'],
            $validated['interest_rate']
        );

        return $this->successResponse('تم حفظ النسبة بنجاح', $tier);
    }

    public function destroy(InstallmentInterestTier $installmentInterestTier)
    {
        $this->tierRepository->delete($installmentInterestTier);

        return $this->successResponse('تم حذف النسبة');
    }
}
