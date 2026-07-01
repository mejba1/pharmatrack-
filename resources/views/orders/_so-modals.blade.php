{{-- ═══════════════ View SO Modal ═══════════════ --}}
<div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" x-show="selectedSO">
      <div class="modal-header">
        <div><h5 class="modal-title fw-semibold" x-text="selectedSO?.id"></h5><div class="text-muted-sm">Sales Order</div></div>
        <div class="ms-auto d-flex gap-2 align-items-center">
          <span class="badge-status" :class="'badge-' + selectedSO?.statusClass" x-text="selectedSO?.status"></span>
          <button class="btn-close ms-2" @click="showViewModal=false"></button>
        </div>
      </div>
      <div class="modal-doc-tabs">
        <button :class="{active: viewTab==='details'}" @click="viewTab='details'"><i class="bi bi-file-text"></i>Order Details</button>
        <button :class="{active: viewTab==='docs'}" @click="viewTab='docs'"><i class="bi bi-paperclip"></i>Reference Documents
          <span class="badge bg-primary rounded-pill py-0 px-1" style="font-size:10px" x-show="(selectedSO?.docs||[]).length>0" x-text="(selectedSO?.docs||[]).length"></span></button>
      </div>
      <div class="modal-body" x-show="viewTab==='details'">
        <div class="row g-3">
          <div class="col-md-6"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">SOLD TO</div><div class="fw-semibold" x-text="selectedSO?.customer"></div><div class="text-muted-sm" x-text="selectedSO?.country"></div></div></div>
          <div class="col-md-6"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">REFERENCE</div><div style="font-size:13px">PO: <span class="text-primary" x-text="selectedSO?.linkedPo"></span></div><div style="font-size:13px">SO Date: <span x-text="selectedSO?.soDate"></span> · Allocated units: <strong x-text="selectedSO?.allocated ?? '—'"></strong></div></div></div>
          <div class="col-12"><div class="p-3 border rounded-3 bg-light">
            <div class="text-muted-sm mb-2 fw-semibold"><i class="bi bi-diagram-2 me-1"></i>DOWNSTREAM STATUS (read-only)</div>
            <div class="d-flex flex-wrap gap-4" style="font-size:13px">
              <div><span class="text-muted">Proforma Invoice:</span>
                <template x-if="selectedSO?.linkedPi"><span><span class="font-monospace" x-text="selectedSO?.linkedPi"></span> <span class="badge bg-secondary-subtle text-secondary" x-text="selectedSO?.piStatus"></span></span></template>
                <template x-if="!selectedSO?.linkedPi"><span class="text-muted">Not issued yet</span></template>
              </div>
              <div><span class="text-muted">Commercial Invoice:</span>
                <template x-if="selectedSO?.ciCount > 0"><span><strong x-text="selectedSO?.ciCount"></strong> raised <span class="badge bg-secondary-subtle text-secondary" x-text="selectedSO?.ciStatus"></span></span></template>
                <template x-if="!selectedSO?.ciCount"><span class="text-muted">None</span></template>
              </div>
            </div>
          </div></div>
          <div class="col-12">
            <table class="table table-sm border rounded-3 overflow-hidden">
              <thead class="table-light"><tr><th>#</th><th>Product</th><th>PRN</th><th>Batch</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
              <tbody>
                <template x-for="(line,i) in (selectedSO?.lines||[])" :key="i">
                  <tr><td x-text="i+1"></td><td class="fw-semibold" style="font-size:13px" x-text="line.product"></td><td class="text-muted-sm" x-text="line.prn"></td><td class="text-muted-sm" x-text="line.batch"></td><td x-text="Number(line.qty).toLocaleString()"></td><td x-text="line.unitPrice"></td><td class="fw-semibold" x-text="line.total"></td></tr>
                </template>
                <tr class="table-light"><td colspan="6" class="text-end fw-semibold">Grand Total</td><td class="fw-bold text-primary" x-text="selectedSO?.value"></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-body" x-show="viewTab==='docs'">
        <form method="POST" :action="docUrl()" enctype="multipart/form-data" class="d-flex gap-2 align-items-end mb-3">
          @csrf
          <div class="flex-grow-1"><label class="form-label">Upload reference document</label><input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.webp" required></div>
          <div style="width:200px"><label class="form-label">Category</label><input type="text" name="category" class="form-control form-control-sm" placeholder="SO Confirmation…"></div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload me-1"></i>Upload</button>
        </form>
        <div x-show="!(selectedSO?.docs||[]).length" class="text-center py-5"><i class="bi bi-folder2-open" style="font-size:40px;opacity:.3;display:block;margin-bottom:10px"></i><div class="text-muted-sm">No reference documents attached yet.</div></div>
        <div class="d-flex flex-column gap-2">
          <template x-for="doc in (selectedSO?.docs||[])" :key="doc.id">
            <div class="doc-item">
              <div class="doc-type-icon" :class="doc.iconClass"><i class="bi bi-file-earmark"></i></div>
              <div class="flex-grow-1" style="min-width:0"><div class="fw-semibold text-truncate" style="font-size:13px" x-text="doc.name"></div>
                <div class="d-flex flex-wrap gap-2 align-items-center mt-1"><span class="badge bg-light text-secondary border" style="font-size:10px" x-text="doc.category"></span><span class="text-muted-sm" x-text="doc.size"></span><span class="text-muted-sm">· <span x-text="doc.uploadedBy"></span> · <span x-text="doc.date"></span></span></div></div>
              <div class="d-flex gap-1 flex-shrink-0">
                <a :href="doc.url" class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-download"></i></a>
                <form method="POST" :action="doc.del" @submit="return confirm('Remove this document?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-trash"></i></button></form>
              </div>
            </div>
          </template>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
        <a :href="pdfUrl(selectedSO)" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>PDF</a>
        <form method="POST" :action="statusUrl()" x-show="selectedSO?.status!=='Cancelled' && selectedSO?.status!=='PI Issued'" @submit="return confirm('Cancel this SO and release its allocated units?')">
          @csrf <input type="hidden" name="action" value="cancel"><button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Cancel SO</button>
        </form>
        <form method="POST" :action="statusUrl()" x-show="selectedSO?.status==='Draft'">@csrf <input type="hidden" name="action" value="confirm"><button class="btn btn-success btn-sm"><i class="bi bi-check2 me-1"></i>Confirm SO</button></form>
        @if(auth()->user()->canModule('invoices'))
        <a :href="'{{ route('orders.pi') }}?so=' + selectedSO?.pid" class="btn btn-primary btn-sm" x-show="selectedSO?.status==='Confirmed'"><i class="bi bi-receipt me-1"></i>Issue Proforma Invoice</a>
        @endif
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false" x-cloak></div>

{{-- ═══════════════ Create SO Modal ═══════════════ --}}
<div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('orders.so.store') }}" x-ref="soForm">
        @csrf
        <input type="hidden" name="status" :value="form.status">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-bag-check me-2 text-primary"></i>Create Sales Order</h5><button type="button" class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="info-box warn mb-3"><i class="bi bi-exclamation-triangle text-warning mt-1"></i><div style="font-size:13px">Pick an acknowledged PO; its lines load below. For each line choose the batch and allocate units by <strong>range</strong> (start–end) or <strong>specific</strong> serials.</div></div>
          <div class="row g-3 mb-2">
            <div class="col-md-5">
              <label class="form-label">Linked PO <span class="text-danger">*</span></label>
              <select name="purchase_order_id" class="form-select form-select-sm" x-model="form.purchase_order_id" @change="onPickPo()" required>
                <option value="">Select acknowledged PO…</option>
                <template x-for="p in ackPos" :key="p.id"><option :value="p.id" x-text="p.number + ' — ' + p.buyer"></option></template>
              </select>
              <div class="text-muted-sm mt-1" x-show="!ackPos.length"><i class="bi bi-exclamation-circle me-1"></i>No acknowledged POs available. Acknowledge a PO first.</div>
            </div>
            <div class="col-md-3"><label class="form-label">SO Date <span class="text-danger">*</span></label><input type="date" name="so_date" class="form-control form-control-sm" x-model="form.so_date" required></div>
            <div class="col-md-2"><label class="form-label">Est. delivery</label><input type="date" name="estimated_delivery_date" class="form-control form-control-sm" x-model="form.estimated_delivery_date"></div>
            <div class="col-md-2"><label class="form-label">Incoterms</label><input type="text" name="incoterms" class="form-control form-control-sm" x-model="form.incoterms" placeholder="FOB"></div>
          </div>

          <div x-show="form.lines.length">
            <label class="form-label fw-semibold">Allocate units per line</label>
            <template x-for="(l,i) in form.lines" :key="i">
              <div class="perm-box mb-2">
                <input type="hidden" :name="`lines[${i}][product_id]`" :value="l.product_id">
                <input type="hidden" :name="`lines[${i}][quantity]`" :value="l.quantity">
                <input type="hidden" :name="`lines[${i}][serials]`" :value="serialStr(l)">
                <div class="d-flex align-items-center mb-2">
                  <span class="fw-semibold" style="font-size:13px" x-text="l.product"></span>
                  <span class="text-muted-sm ms-2" x-text="l.prn"></span>
                  <span class="badge bg-light text-secondary border ms-2" x-text="'PO qty: ' + Number(l.quantity).toLocaleString()"></span>
                </div>
                <div class="row g-2 align-items-end">
                  <div class="col-md-3"><label class="form-label">Batch</label>
                    <select class="form-select form-select-sm" :name="`lines[${i}][batch_id]`" x-model="l.batch_id">
                      <option value="">— auto / none —</option>
                      <template x-for="b in batchesFor(l.product_id)" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
                    </select>
                  </div>
                  <div class="col-md-2"><label class="form-label">Mode</label>
                    <div class="btn-group btn-group-sm w-100" role="group">
                      <button type="button" class="btn" :class="l.mode==='range'?'btn-primary':'btn-outline-secondary'" @click="l.mode='range'">Range</button>
                      <button type="button" class="btn" :class="l.mode==='specific'?'btn-primary':'btn-outline-secondary'" @click="l.mode='specific'">Specific</button>
                    </div>
                  </div>
                  <template x-if="l.mode==='range'">
                    <div class="col-md-3 d-flex gap-1">
                      <div><label class="form-label">Start</label><input type="number" min="1" class="form-control form-control-sm" x-model.number="l.start"></div>
                      <div><label class="form-label">End</label><input type="number" min="1" class="form-control form-control-sm" x-model.number="l.end"></div>
                    </div>
                  </template>
                  <template x-if="l.mode==='specific'">
                    <div class="col-md-3"><label class="form-label">Serials</label><input type="text" class="form-control form-control-sm" x-model="l.serials" placeholder="1,3,6 or 1-5,10-12"></div>
                  </template>
                  <div class="col-md-2"><label class="form-label">Unit price</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" :name="`lines[${i}][unit_price]`" x-model="l.unit_price"></div>
                  <div class="col-md-2"><div class="text-muted-sm">Allocating</div><div class="fw-bold" x-text="parseCount(l).toLocaleString() + ' units'"></div></div>
                </div>
              </div>
            </template>
            <div class="d-flex justify-content-end"><div class="text-end"><div class="text-muted-sm">Order total</div><div class="fw-bold fs-5" x-text="(chosenPo?.currency||'USD') + ' ' + grandTotal"></div></div></div>
            <div class="mt-2"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control form-control-sm" rows="2" x-model="form.remarks"></textarea></div>
          </div>
          <div x-show="form.purchase_order_id && !form.lines.length" class="text-muted-sm py-3">This PO has no order lines.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="showAddModal=false">Cancel</button>
          <button type="button" class="btn btn-outline-primary btn-sm" @click="submitForm('draft')" :disabled="!form.lines.length"><i class="bi bi-floppy me-1"></i>Save Draft</button>
          <button type="button" class="btn btn-primary btn-sm" @click="submitForm('confirmed')" :disabled="!form.lines.length"><i class="bi bi-check-lg me-1"></i>Confirm SO</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false" x-cloak></div>
