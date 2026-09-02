<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SmsPackage;
use Illuminate\Http\Request;

class SmsPackageController extends Controller
{
    public function index()
    {
        $packages = SmsPackage::query()->orderBy('sort_order')->orderBy('sms_count')->get();

        return view('super-admin.sms-packages.index', compact('packages'));
    }

    public function create()
    {
        return view('super-admin.sms-packages.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        SmsPackage::create($data);

        return redirect()->route('super-admin.sms-packages.index')
            ->with('status', 'SMS package created.');
    }

    public function edit(SmsPackage $smsPackage)
    {
        return view('super-admin.sms-packages.edit', ['package' => $smsPackage]);
    }

    public function update(Request $request, SmsPackage $smsPackage)
    {
        $smsPackage->update($this->validated($request));

        return redirect()->route('super-admin.sms-packages.index')
            ->with('status', 'SMS package updated.');
    }

    public function destroy(SmsPackage $smsPackage)
    {
        $smsPackage->delete();

        return redirect()->route('super-admin.sms-packages.index')
            ->with('status', 'SMS package deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sms_count' => ['required', 'integer', 'min:1', 'max:1000000'],
            'price_etb' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['price_per_sms'] = round((float) $data['price_etb'] / (int) $data['sms_count'], 4);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
