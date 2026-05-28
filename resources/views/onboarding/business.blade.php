@extends('layouts.auth')

@section('title', 'Business Verification — BookInStack')

@section('content')

<div style="text-align:center;margin-bottom:32px;">
    <a href="/" style="text-decoration:none;">
        <span style="font-size:22px;font-weight:800;color:#0d0d14;letter-spacing:-.4px;">
            BookIn<span style="color:#4f46e5;">Stack</span>
        </span>
    </a>
</div>

@include('onboarding._progress', ['step' => 2])

<h2 style="font-size:22px;font-weight:700;color:#0d0d14;margin-bottom:6px;letter-spacing:-.4px;">
    Verify your business
</h2>
<p style="font-size:14px;color:#64748b;margin-bottom:24px;line-height:1.6;">
    Fill in your business and director details. We'll review your documents and set up your payment account.
</p>

@if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:20px;">
        ⚠️ {{ session('error') }}
    </div>
@endif

<form method="POST" action="{{ route('onboarding.business.submit') }}" enctype="multipart/form-data">
    @csrf

    {{-- ── Business Details ── --}}
    <p style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;">Business Details</p>

    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Business Name (as registered with CAC)</label>
        <input type="text" name="business_name" value="{{ old('business_name', $developer->business_name) }}"
            placeholder="Okafor Events Ltd" required
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
        @error('business_name')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">RC Number</label>
            <input type="text" name="rc_number" value="{{ old('rc_number') }}"
                placeholder="RC1234567" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('rc_number')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Registration Type</label>
            <select name="registration_type" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;color:#0d0d14;">
                <option value="">Select</option>
                <option value="Private_Incorporated" {{ old('registration_type') === 'Private_Incorporated' ? 'selected' : '' }}>Private Limited (Ltd)</option>
                <option value="Public_Incorporated" {{ old('registration_type') === 'Public_Incorporated' ? 'selected' : '' }}>Public Limited (PLC)</option>
                <option value="Business_Name" {{ old('registration_type') === 'Business_Name' ? 'selected' : '' }}>Business Name</option>
                <option value="Incorporated_Trustee" {{ old('registration_type') === 'Incorporated_Trustee' ? 'selected' : '' }}>Incorporated Trustee (NGO)</option>
            </select>
            @error('registration_type')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Date of Registration</label>
            <input type="date" name="date_of_registration" value="{{ old('date_of_registration') }}" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('date_of_registration')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Industry</label>
            {{-- Replace the industry <select> in business.blade.php with this ────────────
     Values are the exact Anchor API enums; labels are human-readable         --}}

             <select class="form-control" name="industry" id="industry" required>
                                            <option value="">Select Industry</option>

                                            <optgroup label="Agriculture"
                                                {{ old('industry') == 'Agriculture' ? 'selected' : '' }}>
                                                <option value="Agriculture-AgriculturalCooperatives">Agricultural
                                                    Cooperatives</option>
                                                <option value="Agriculture-AgriculturalServices">Agricultural Services
                                                </option>
                                            </optgroup>

                                            <optgroup label="Commerce">
                                                <option value="Commerce-Automobiles">Automobiles</option>
                                                <option value="Commerce-DigitalGoods">Digital Goods</option>
                                                <option value="Commerce-PhysicalGoods">Physical Goods</option>
                                                <option value="Commerce-RealEstate">Real Estate</option>
                                                <option value="Commerce-DigitalServices">Digital Services</option>
                                                <option value="Commerce-LegalServices">Legal Services</option>
                                                <option value="Commerce-PhysicalServices">Physical Services</option>
                                                <option value="Commerce-ProfessionalServices">Professional Services</option>
                                                <option value="Commerce-OtherProfessionalServices">Other Professional
                                                    Services</option>
                                            </optgroup>

                                            <optgroup label="Education">
                                                <option value="Education-NurserySchools">Nursery Schools</option>
                                                <option value="Education-PrimarySchools">Primary Schools</option>
                                                <option value="Education-SecondarySchools">Secondary Schools</option>
                                                <option value="Education-TertiaryInstitutions">Tertiary Institutions
                                                </option>
                                                <option value="Education-VocationalTraining">Vocational Training</option>
                                                <option value="Education-VirtualLearning">Virtual Learning</option>
                                                <option value="Education-OtherEducationalServices">Other Educational
                                                    Services</option>
                                            </optgroup>

                                            <optgroup label="Gaming">
                                                <option value="Gaming-Betting">Betting</option>
                                                <option value="Gaming-Lotteries">Lotteries</option>
                                                <option value="Gaming-PredictionServices">Prediction Services</option>
                                            </optgroup>

                                            <optgroup label="Financial Services">
                                                <option value="FinancialServices-FinancialCooperatives">Financial
                                                    Cooperatives</option>
                                                <option value="FinancialServices-CorporateServices">Corporate Services
                                                </option>
                                                <option value="FinancialServices-PaymentSolutionServiceProviders">Payment
                                                    Solution Service Providers</option>
                                                <option value="FinancialServices-Insurance">Insurance</option>
                                                <option value="FinancialServices-Investments">Investments</option>
                                                <option value="FinancialServices-AgriculturalInvestments">Agricultural
                                                    Investments</option>
                                                <option value="FinancialServices-Lending">Lending</option>
                                                <option value="FinancialServices-BillPayments">Bill Payments</option>
                                                <option value="FinancialServices-Payroll">Payroll</option>
                                                <option value="FinancialServices-Remittances">Remittances</option>
                                                <option value="FinancialServices-Savings">Savings</option>
                                                <option value="FinancialServices-MobileWallets">Mobile Wallets</option>
                                            </optgroup>

                                            <optgroup label="Health">
                                                <option value="Health-Gyms">Gyms</option>
                                                <option value="Health-Hospitals">Hospitals</option>
                                                <option value="Health-Pharmacies">Pharmacies</option>
                                                <option value="Health-HerbalMedicine">Herbal Medicine</option>
                                                <option value="Health-Telemedicine">Telemedicine</option>
                                                <option value="Health-MedicalLaboratories">Medical Laboratories</option>
                                            </optgroup>

                                            <optgroup label="Hospitality">
                                                <option value="Hospitality-Hotels">Hotels</option>
                                                <option value="Hospitality-Restaurants">Restaurants</option>
                                            </optgroup>

                                            <optgroup label="Nonprofits">
                                                <option value="Nonprofits-ProfessionalAssociations">Professional
                                                    Associations</option>
                                                <option value="Nonprofits-GovernmentAgencies">Government Agencies</option>
                                                <option value="Nonprofits-NGOs">NGOs</option>
                                                <option value="Nonprofits-PoliticalParties">Political Parties</option>
                                                <option value="Nonprofits-ReligiousOrganizations">Religious Organizations
                                                </option>
                                                <option value="Nonprofits-Leisure_And_Entertainment">Leisure And
                                                    Entertainment</option>
                                                <option value="Nonprofits-Cinemas">Cinemas</option>
                                                <option value="Nonprofits-Nightclubs">Nightclubs</option>
                                                <option value="Nonprofits-Events">Events</option>
                                                <option value="Nonprofits-Press_And_Media">Press And Media</option>
                                                <option value="Nonprofits-RecreationCentres">Recreation Centres</option>
                                                <option value="Nonprofits-StreamingServices">Streaming Services</option>
                                            </optgroup>

                                            <optgroup label="Logistics">
                                                <option value="Logistics-CourierServices">Courier Services</option>
                                                <option value="Logistics-FreightServices">Freight Services</option>
                                            </optgroup>

                                            <optgroup label="Travel">
                                                <option value="Travel-Airlines">Airlines</option>
                                                <option value="Travel-Ridesharing">Ridesharing</option>
                                                <option value="Travel-TourServices">Tour Services</option>
                                                <option value="Travel-Transportation">Transportation</option>
                                                <option value="Travel-TravelAgencies">Travel Agencies</option>
                                            </optgroup>

                                            <optgroup label="Utilities">
                                                <option value="Utilities-CableTelevision">Cable Television</option>
                                                <option value="Utilities-Electricity">Electricity</option>
                                                <option value="Utilities-GarbageDisposal">Garbage Disposal</option>
                                                <option value="Utilities-Internet">Internet</option>
                                                <option value="Utilities-Telecoms">Telecoms</option>
                                                <option value="Utilities-Water">Water</option>
                                            </optgroup>

                                            <optgroup label="Other Industries">
                                                <option value="Retail">Retail</option>
                                                <option value="Wholesale">Wholesale</option>
                                                <option value="Restaurants">Restaurants</option>
                                                <option value="Construction">Construction</option>
                                                <option value="Unions">Unions</option>
                                                <option value="RealEstate">Real Estate</option>
                                                <option value="FreelanceProfessional">Freelance Professional</option>
                                                <option value="OtherProfessionalServices">Other Professional Services
                                                </option>
                                                <option value="OnlineRetailer">Online Retailer</option>
                                                <option value="OtherEducationServices">Other Education Services</option>
                                            </optgroup>
                                        </select>
            @error('industry')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Phone Number</label>
            <input type="tel" name="phone_number" value="{{ old('phone_number') }}"
                placeholder="08012345678" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('phone_number')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Website <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
            <input type="url" name="website" value="{{ old('website') }}"
                placeholder="https://example.com"
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
        </div>
    </div>

    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Business Address</label>
        <input type="text" name="address_line1" value="{{ old('address_line1') }}"
            placeholder="12 Broad Street" required
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
        @error('address_line1')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">City</label>
            <input type="text" name="city" value="{{ old('city') }}" placeholder="Lagos" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">State</label>
            <select name="state" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;color:#0d0d14;">
                <option value="">Select state</option>
                @foreach(['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'] as $st)
                    <option value="{{ $st }}" {{ old('state') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ── Director ── --}}
    <p style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;">Director / Business Owner</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">First Name</label>
            <input type="text" name="director_first_name" value="{{ old('director_first_name') }}" placeholder="Emeka" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('director_first_name')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Last Name</label>
            <input type="text" name="director_last_name" value="{{ old('director_last_name') }}" placeholder="Okafor" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('director_last_name')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Date of Birth</label>
            <input type="date" name="director_dob" value="{{ old('director_dob') }}" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('director_dob')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">ID Type</label>
            <select name="director_id_type" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;color:#0d0d14;">
                <option value="">Select</option>
                <option value="NIN_SLIP" {{ old('director_id_type') === 'NIN_SLIP' ? 'selected' : '' }}>NIN Slip</option>
                <option value="DRIVERS_LICENSE" {{ old('director_id_type') === 'DRIVERS_LICENSE' ? 'selected' : '' }}>Driver's License</option>
                <option value="INTL_PASSPORT" {{ old('director_id_type') === 'INTL_PASSPORT' ? 'selected' : '' }}>International Passport</option>
            </select>
            @error('director_id_type')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">ID Number</label>
            <input type="text" name="director_id_number" value="{{ old('director_id_number') }}" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;" />
            @error('director_id_number')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Director BVN</label>
            <input type="text" name="director_bvn" value="{{ old('director_bvn') }}"
                placeholder="12345678901" maxlength="11" required
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;letter-spacing:.05em;" />
            @error('director_bvn')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- ── Documents ── --}}
    <p style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px;">Business Documents</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:14px;line-height:1.5;">
        Upload your CAC documents. Our team will verify and manually approve your business (usually within 24–48 hours).
    </p>

    @foreach([['cac_document','CAC Certificate / Status Report','required'],['memart_document','MEMART or Articles of Association','optional'],['utility_bill','Utility Bill (not older than 3 months)','optional']] as [$field,$label,$req])
    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">
            {{ $label }}
            @if($req === 'required') <span style="color:#ef4444;">*</span>
            @else <span style="font-weight:400;color:#94a3b8;">(optional)</span>
            @endif
        </label>
        <input type="file" name="{{ $field }}"
            accept=".pdf,.jpg,.jpeg,.png"
            {{ $req === 'required' ? 'required' : '' }}
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;background:#fff;cursor:pointer;color:#374151;" />
        <p style="font-size:11px;color:#94a3b8;margin-top:3px;">PDF, JPG or PNG · max 5MB</p>
        @error($field)<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>
    @endforeach

    {{-- ── Payout Bank ── --}}
    <p style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;margin:20px 0 8px;">Payout Bank Account</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:14px;line-height:1.5;">
        Your business bank account — we'll auto-transfer earnings here after every booking payment.
    </p>

    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Bank</label>
        <select name="settlement_bank_nip_code" id="bank-select" required
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;color:#0d0d14;">
            <option value="">Select bank</option>
            @foreach($banks ?? [] as $bank)
                <option value="{{ $bank['attributes']['nipCode'] }}"
                    {{ old('settlement_bank_nip_code') === $bank['attributes']['nipCode'] ? 'selected' : '' }}>
                    {{ $bank['attributes']['name'] }}
                </option>
            @endforeach
        </select>
        @error('settlement_bank_nip_code')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>

    <div style="margin-bottom:28px;">
        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px;">Account Number</label>
        <input type="text" name="settlement_account_number" value="{{ old('settlement_account_number') }}"
            placeholder="0123456789" maxlength="10" required
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;outline:none;background:#fff;letter-spacing:.05em;" />
        <div id="account-name-preview" style="font-size:12px;color:#10b981;margin-top:5px;font-weight:600;min-height:18px;"></div>
        @error('settlement_account_number')<div style="color:#ef4444;font-size:11px;margin-top:3px;">{{ $message }}</div>@enderror
    </div>

    <button type="submit" style="
        width:100%;padding:13px;background:#4f46e5;color:#fff;
        border:none;border-radius:10px;font-size:15px;font-weight:600;
        cursor:pointer;transition:background .15s;
    ">
        Submit for Review →
    </button>

    <p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:16px;line-height:1.6;">
        🔐 Documents are handled securely and only used for KYB verification.
    </p>

</form>

@push('scripts')
<script>
document.querySelector('[name="director_bvn"]').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 11);
});
document.querySelector('[name="settlement_account_number"]').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
    if (this.value.length === 9) resolveAccount();
    else document.getElementById('account-name-preview').textContent = '';
});

async function resolveAccount() {
    const acct = document.querySelector('[name="settlement_account_number"]').value;
    const bank = document.getElementById('bank-select').value;
    const preview = document.getElementById('account-name-preview');
    if (!bank) return;
    preview.textContent = 'Verifying...';
    preview.style.color = '#94a3b8';
    try {
        const res  = await fetch(`/onboarding/resolve-account?bank=${bank}&account=${acct}`);
        const json = await res.json();
        if (json.account_name) {
            preview.textContent = '✓ ' + json.account_name;
            preview.style.color = '#10b981';
        } else {
            preview.textContent = 'Account not found';
            preview.style.color = '#ef4444';
        }
    } catch {
        preview.textContent = 'Could not verify';
        preview.style.color = '#ef4444';
    }
}

document.getElementById('bank-select').addEventListener('change', () => {
    if (document.querySelector('[name="settlement_account_number"]').value.length === 10) resolveAccount();
});
</script>
@endpush

@endsection