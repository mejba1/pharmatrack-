{{-- ═══════════════ View PI Modal ═══════════════ --}}
<div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" x-show="selectedPI">
      <div class="modal-header">
        <div><h5 class="modal-title fw-semibold" x-text="selectedPI?.id"></h5><div class="text-muted-sm">Proforma Invoice</div></div>
        <div class="ms-auto d-flex gap-2 align-items-center"><span class="badge-status" :class="'badge-' + selectedPI?.statusClass" x-text="selectedPI?.status"></span><button class="btn-close ms-2" @click="showViewModal=false"></button></div>
      </div>
      <div class="modal-doc-tabs">
        <button :class="{active: viewTab==='details'}" @click="viewTab='details'"><i class="bi bi-file-text"></i>Invoice Details</button>
        <button :class="{active: viewTab==='docs'}" @click="viewTab='docs'"><i class="bi bi-paperclip"></i>Reference Documents
          <span class="badge bg-primary rounded-pill py-0 px-1" style="font-size:10px" x-show="(selectedPI?.docs||[]).length>0" x-text="(selectedPI?.docs||[]).length"></span></button>
      </div>
      <div class="modal-body" x-show="viewTab==='details'">
        <div class="alert alert-danger py-2 small" x-show="selectedPI?.status==='Rejected' && selectedPI?.rejection"><i class="bi bi-x-circle me-1"></i><strong>Rejected:</strong> <span x-text="selectedPI?.rejection"></span></div>
        <div class="row g-3">
          <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">BILL TO</div><div class="fw-semibold" x-text="selectedPI?.customer"></div><div class="text-muted-sm" x-text="selectedPI?.country"></div></div></div>
          <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">REFERENCE</div><div style="font-size:13px">SO: <span class="text-primary" x-text="selectedPI?.linkedSo"></span></div><div style="font-size:13px">PI Date: <span x-text="selectedPI?.piDate"></span></div><div style="font-size:13px">Valid: <span x-text="selectedPI?.validUntil"></span></div></div></div>
          <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">BANKING</div><div style="font-size:13px" x-text="selectedPI?.bank || '—'"></div><div class="text-muted-sm">SWIFT: <span x-text="selectedPI?.swift || '—'"></span></div><div class="text-muted-sm">Terms: <span x-text="selectedPI?.payTerms || '—'"></span></div></div></div>
          <div class="col-12">
            <table class="table table-sm border rounded-3 overflow-hidden">
              <thead class="table-light"><tr><th>#</th><th>Product</th><th>PRN</th><th>Batch</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
              <tbody>
                <template x-for="(line,i) in (selectedPI?.lines||[])" :key="i">
                  <tr><td x-text="i+1"></td><td class="fw-semibold" style="font-size:13px" x-text="line.product"></td><td class="text-muted-sm" x-text="line.prn"></td><td class="text-muted-sm" x-text="line.batch"></td><td x-text="Number(line.qty).toLocaleString()"></td><td x-text="line.unitPrice"></td><td class="fw-semibold" x-text="line.total"></td></tr>
                </template>
                <tr class="table-light"><td colspan="6" class="text-end fw-semibold">Total Value</td><td class="fw-bold text-primary" x-text="selectedPI?.value"></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-body" x-show="viewTab==='docs'">
        <form method="POST" :action="docUrl()" enctype="multipart/form-data" class="d-flex gap-2 align-items-end mb-3">@csrf
          <div class="flex-grow-1"><label class="form-label">Upload reference document</label><input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.webp" required></div>
          <div style="width:200px"><label class="form-label">Category</label><input type="text" name="category" class="form-control form-control-sm" placeholder="COA, Finance Approval…"></div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload me-1"></i>Upload</button>
        </form>
        <div x-show="!(selectedPI?.docs||[]).length" class="text-center py-5"><i class="bi bi-folder2-open" style="font-size:40px;opacity:.3;display:block;margin-bottom:10px"></i><div class="text-muted-sm">No reference documents attached yet.</div></div>
        <div class="d-flex flex-column gap-2">
          <template x-for="doc in (selectedPI?.docs||[])" :key="doc.id">
            <div class="doc-item"><div class="doc-type-icon" :class="doc.iconClass"><i class="bi bi-file-earmark"></i></div>
              <div class="flex-grow-1" style="min-width:0"><div class="fw-semibold text-truncate" style="font-size:13px" x-text="doc.name"></div>
                <div class="d-flex flex-wrap gap-2 align-items-center mt-1"><span class="badge bg-light text-secondary border" style="font-size:10px" x-text="doc.category"></span><span class="text-muted-sm" x-text="doc.size"></span><span class="text-muted-sm">· <span x-text="doc.uploadedBy"></span> · <span x-text="doc.date"></span></span></div></div>
              <div class="d-flex gap-1 flex-shrink-0"><a :href="doc.url" class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-download"></i></a>
                <form method="POST" :action="doc.del" @submit="return confirm('Remove this document?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-trash"></i></button></form></div>
            </div>
          </template>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
        <a :href="pdfUrl(selectedPI)" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>PDF</a>
        <form method="POST" :action="statusUrl()" x-show="selectedPI?.status==='Draft' || selectedPI?.status==='Rejected'">@csrf<input type="hidden" name="action" value="send"><button class="btn btn-warning btn-sm"><i class="bi bi-send me-1"></i>Send to Finance</button></form>
        <form method="POST" :action="statusUrl()" x-show="selectedPI?.status==='Pending approval'" @submit="return confirm('Approve this PI?')">@csrf<input type="hidden" name="action" value="approve"><button class="btn btn-success btn-sm"><i class="bi bi-check2 me-1"></i>Approve PI</button></form>
        <a href="{{ route('orders.ci') }}" class="btn btn-primary btn-sm" x-show="selectedPI?.status==='Approved'"><i class="bi bi-file-earmark-check me-1"></i>Raise Commercial Invoice</a>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false" x-cloak></div>

{{-- ═══════════════ Issue PI Modal ═══════════════ --}}
<div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('orders.pi.store') }}" x-ref="piForm">@csrf
        <input type="hidden" name="status" :value="form.status">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-receipt me-2 text-primary"></i>Issue Proforma Invoice</h5><button type="button" class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12"><div class="info-box info"><i class="bi bi-info-circle text-primary mt-1"></i><div style="font-size:13px">Lines, customer and totals are pulled from the selected confirmed Sales Order.</div></div></div>
            <div class="col-md-7">
              <label class="form-label">Linked Sales Order <span class="text-danger">*</span></label>
              <select name="sales_order_id" class="form-select form-select-sm" x-model="form.sales_order_id" @change="onPickSo()" required>
                <option value="">Select confirmed SO…</option>
                <template x-for="s in confirmedSos" :key="s.id"><option :value="s.id" x-text="s.number + ' — ' + s.customer + ' · ' + s.currency + ' ' + s.total.toLocaleString()"></option></template>
              </select>
              <div class="text-muted-sm mt-1" x-show="!confirmedSos.length"><i class="bi bi-exclamation-circle me-1"></i>No confirmed SOs available. Confirm a Sales Order first.</div>
              <div class="text-muted-sm mt-1" x-show="chosenSo"><span x-text="chosenSo?.lineCount"></span> line(s) · <span x-text="Number(chosenSo?.units).toLocaleString()"></span> units will be invoiced.</div>
            </div>
            <div class="col-md-5"><div class="row g-2">
              <div class="col-6"><label class="form-label">PI Date</label><input type="date" name="pi_date" class="form-control form-control-sm" x-model="form.pi_date" required></div>
              <div class="col-6"><label class="form-label">Valid Until</label><input type="date" name="valid_until" class="form-control form-control-sm" x-model="form.valid_until" required></div>
            </div></div>
            <div class="col-md-3"><label class="form-label">Currency</label><input type="text" name="currency" maxlength="3" class="form-control form-control-sm text-uppercase" x-model="form.currency"></div>
            <div class="col-md-3"><label class="form-label">Incoterms</label><input type="text" name="incoterms" class="form-control form-control-sm" x-model="form.incoterms" placeholder="FOB"></div>
            <div class="col-md-3"><label class="form-label">Payment terms</label><input type="text" name="payment_terms" class="form-control form-control-sm" x-model="form.payment_terms"></div>
            <div class="col-md-3"><label class="form-label">Freight</label><input type="number" min="0" step="0.01" name="freight" class="form-control form-control-sm" x-model="form.freight" placeholder="0.00"></div>

            <div class="col-12"><div class="text-muted-sm text-uppercase fw-bold" style="font-size:11px"><i class="bi bi-bank me-1"></i>Banking (for the invoice)</div></div>
            <div class="col-md-6"><label class="form-label">Bank name</label><input type="text" name="bank_name" class="form-control form-control-sm" x-model="form.bank_name"></div>
            <div class="col-md-3"><label class="form-label">SWIFT</label><input type="text" name="bank_swift_code" class="form-control form-control-sm" x-model="form.bank_swift_code"></div>
            <div class="col-md-3"><label class="form-label">Account no.</label><input type="text" name="bank_account_number" class="form-control form-control-sm" x-model="form.bank_account_number"></div>
            <div class="col-md-6"><label class="form-label">IBAN</label><input type="text" name="bank_iban" class="form-control form-control-sm" x-model="form.bank_iban"></div>
            <div class="col-md-6"><label class="form-label">Port of loading</label><input type="text" name="port_of_loading" class="form-control form-control-sm" x-model="form.port_of_loading"></div>
            <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control form-control-sm" rows="2" x-model="form.remarks"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="showAddModal=false">Cancel</button>
          <button type="button" class="btn btn-outline-primary btn-sm" @click="submitForm('draft')" :disabled="!form.sales_order_id"><i class="bi bi-floppy me-1"></i>Save Draft</button>
          <button type="button" class="btn btn-primary btn-sm" @click="submitForm('sent')" :disabled="!form.sales_order_id"><i class="bi bi-send me-1"></i>Issue &amp; Send for Approval</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false" x-cloak></div>
