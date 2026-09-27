<!-- Modal Export Excel -->
<div class="modal fade" id="exportExcelModal" tabindex="-1" aria-labelledby="exportExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: var(--palette-5);">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="exportExcelModalLabel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Pilih Periode Ekspor Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formExportExcel" action="{{ route('reports.puskesad.export') }}" method="GET" target="_blank">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Silakan pilih bulan, tahun, dan poliklinik data yang ingin Anda ekspor ke format Microsoft Excel (.xlsx).</p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted mb-1">Bulan / Periode</label>
                        <select name="month" id="excelExportMonth" class="form-select">
                            @foreach($availableMonths as $num => $name)
                                <option value="{{ $num }}" {{ (isset($month) && (string)$month == (string)$num) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted mb-1">Tahun Periode</label>
                        <select name="year" id="excelExportYear" class="form-select">
                            <option value="SEMUA" {{ (isset($year) && $year == 'SEMUA') ? 'selected' : '' }}>-- SEMUA TAHUN --</option>
                            @if(isset($dbYears) && count($dbYears) > 0)
                                @foreach($dbYears as $y)
                                    <option value="{{ $y }}" {{ (isset($year) && (string)$year == (string)$y) ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            @else
                                @foreach(range(2024, 2030) as $y)
                                    <option value="{{ $y }}" {{ (isset($year) && (string)$year == (string)$y) ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="mb-3" id="excelExportPoliGroup">
                        <label class="form-label fw-semibold small text-muted mb-1">Poliklinik</label>
                        <select name="poli" id="excelExportPoli" class="form-select">
                            <option value="SEMUA">-- SEMUA POLIKLINIK --</option>
                            @if(isset($polikliniks))
                                @foreach($polikliniks as $pName)
                                    <option value="{{ $pName }}" {{ (isset($poli) && $poli == $pName) ? 'selected' : '' }}>{{ $pName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-rspad-primary px-4 fw-semibold" onclick="setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('exportExcelModal'))?.hide(); }, 500)">
                        Download Excel (.xlsx)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
