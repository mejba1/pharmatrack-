{{-- ═══════════════ View CI Modal ═══════════════ --}}
<div class="modal fade" :class="{show:showViewModal}" :style="showViewModal?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" x-show="selectedCI">
      <div class="modal-header">
        <div><h5 class="modal-title fw-semibold" x-text="selectedCI?.id"></h5><div class="text-muted-sm">Commercial Invoice</div></div>
        <div class="ms-auto d-flex gap-2 align-items-center"><span class="badge-status" :class="'badge-' + selectedCI?.statusClass" x-text="selectedCI?.status"></span><button class="btn-close ms-2" @click="showViewModal=false"></button></div>
      </div>
      <div class="modal-doc-tabs">
        <button :class="{active: viewTab==='details'}" @click="viewTab='details'"><i class="bi bi-file-text"></i>Invoice Details</button>
        <button :class="{active: viewTab==='docs'}" @click="viewTab='docs'"><i class="bi bi-paperclip"></i>Reference Documents
          <span class="badge bg-primary rounded-pill py-0 px-1" style="font-size:10px" x-show="(selectedCI?.docs||[]).length>0" x-text="(selectedCI?.docs||[]).length"></span></button>
      </div>
      <div class="modal-body" x-show="viewTab==='details'">
        <div class="row g-3">
          <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">CONSIGNEE</div><div class="fw-semibold" x-text="selectedCI?.customer"></div><div class="text-muted-sm" x-text="selectedCI?.country"></div></div></div>
          <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">REFERENCE</div><div style="font-size:13px">PI: <span class="text-primary" x-text="selectedCI?.linkedPi"></span></div><div style="font-size:13px">CI Date: <span x-text="selectedCI?.ciDate"></span></div></div></div>
          <div class="col-md-4"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-1 fw-semibold">CUSTOMS</div><div style="font-size:13px">HS Code: <span x-text="selectedCI?.hsCode || '—'"></span></div><div class="text-muted-sm">Origin: <span x-text="selectedCI?.origin || '—'"></span> · <span x-text="selectedCI?.incoterms || '—'"></span></div></div></div>
          <div class="col-12">
            <table class="table table-sm border rounded-3 overflow-hidden">
              <thead class="table-light"><tr><th>#</th><th>Product</th><th>PRN</th><th>Batch</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
              <tbody>
                <template x-for="(line,i) in (selectedCI?.lines||[])" :key="i">
                  <tr><td x-text="i+1"></td><td class="fw-semibold" style="font-size:13px" x-text="line.product"></td><td class="text-muted-sm" x-text="line.prn"></td><td class="text-muted-sm" x-text="line.batch"></td><td x-text="Number(line.qty).toLocaleString()"></td><td x-text="line.unitPrice"></td><td class="fw-semibold" x-text="line.total"></td></tr>
                </template>
                <tr class="table-light"><td colspan="6" class="text-end fw-semibold">Total Value</td><td class="fw-bold text-primary" x-text="selectedCI?.value"></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-body" x-show="viewTab==='docs'">
        <form method="POST" :action="docUrl()" enctype="multipart/form-data" class="d-flex gap-2 align-items-end mb-3">@csrf
          <div class="flex-grow-1"><label class="form-label">Upload reference document</label><input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.webp" required></div>
          <div style="width:200px"><label class="form-label">Category</label><input type="text" name="category" class="form-control form-control-sm" placeholder="Packing List, COO…"></div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload me-1"></i>Upload</button>
        </form>
        <div x-show="!(selectedCI?.docs||[]).length" class="text-center py-5"><i class="bi bi-folder2-open" style="font-size:40px;opacity:.3;display:block;margin-bottom:10px"></i><div class="text-muted-sm">No reference documents attached yet.</div></div>
        <div class="d-flex flex-column gap-2">
          <template x-for="doc in (selectedCI?.docs||[])" :key="doc.id">
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
        <a :href="pdfUrl(selectedCI)" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>PDF</a>
        <form method="POST" :action="statusUrl()" x-show="selectedCI?.status==='Draft'">@csrf<input type="hidden" name="action" value="send"><button class="btn btn-warning btn-sm"><i class="bi bi-send me-1"></i>Send for Approval</button></form>
        <form method="POST" :action="statusUrl()" x-show="selectedCI?.status==='Pending approval'" @submit="return confirm('Approve this CI?')">@csrf<input type="hidden" name="action" value="approve"><button class="btn btn-success btn-sm"><i class="bi bi-check2 me-1"></i>Approve CI</button></form>
        <a href="{{ route('shipments') }}" class="btn btn-primary btn-sm" x-show="selectedCI?.status==='Approved'"><i class="bi bi-truck me-1"></i>Create Shipment</a>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false" x-cloak></div>

{{-- ═══════════════ Raise CI Modal ═══════════════ --}}
<div class="modal fade" :class="{show:showAddModal}" :style="showAddModal?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('orders.ci.store') }}" x-ref="ciForm">@csrf
        <input type="hidden" name="status" :value="form.status">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Raise Commercial Invoice</h5><button type="button" class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="info-box info mb-3"><i class="bi bi-info-circle text-primary mt-1"></i><div style="font-size:13px">Pick an approved PI; each line defaults to its <strong>remaining</strong> quantity. Invoice the full balance or less for a <strong>partial CI</strong> — the rest stays available for future CIs.</div></div>
          <div class="row g-3 mb-2">
            <div class="col-md-5">
              <label class="form-label">Approved PI <span class="text-danger">*</span></label>
              <select name="proforma_invoice_id" class="form-select form-select-sm" x-model="form.proforma_invoice_id" @change="onPickPi()" required>
                <option value="">Select approved PI…</option>
                <template x-for="p in approvedPis" :key="p.id"><option :value="p.id" x-text="p.number + ' — ' + p.customer"></option></template>
              </select>
              <div class="text-muted-sm mt-1" x-show="!approvedPis.length"><i class="bi bi-exclamation-circle me-1"></i>No approved PIs with remaining quantity.</div>
            </div>
            <div class="col-md-2"><label class="form-label">CI Date <span class="text-danger">*</span></label><input type="date" name="ci_date" class="form-control form-control-sm" x-model="form.ci_date" required></div>
            <div class="col-md-2"><label class="form-label">HS Code</label><input type="text" name="hs_code" class="form-control form-control-sm" x-model="form.hs_code" placeholder="3004.20"></div>
            <div class="col-md-1"><label class="form-label">Origin</label><input type="text" name="country_of_origin" maxlength="5" class="form-control form-control-sm text-uppercase" x-model="form.country_of_origin"></div>
            <div class="col-md-2"><label class="form-label">Incoterms</label><input type="text" name="incoterms" class="form-control form-control-sm" x-model="form.incoterms" placeholder="CIF"></div>
          </div>

          <div x-show="form.lines.length">
            <label class="form-label fw-semibold">Invoice quantities (partial allowed)</label>
            <table class="table table-sm border rounded-3">
              <thead class="table-light"><tr><th style="width:30%">Product</th><th>Remaining</th><th>Invoice Qty</th><th>Unit Price</th><th>Net kg</th><th>Gross kg</th><th>Total</th></tr></thead>
              <tbody>
                <template x-for="(l,i) in form.lines" :key="i">
                  <tr :class="overLimit(l) ? 'table-danger' : ''">
                    <td><div class="fw-semibold" style="font-size:13px" x-text="l.product"></div><div class="text-muted-sm" x-text="l.prn"></div>
                      <input type="hidden" :name="`lines[${i}][pi_line_id]`" :value="l.pi_line_id"></td>
                    <td><span class="badge bg-light text-secondary border" x-text="Number(l.remaining).toLocaleString() + ' / ' + Number(l.ordered).toLocaleString()"></span></td>
                    <td style="max-width:110px"><input type="number" min="0" :max="l.remaining" class="form-control form-control-sm" :name="`lines[${i}][quantity]`" x-model="l.quantity">
                      <div class="text-danger" style="font-size:10px" x-show="overLimit(l)">Max <span x-text="l.remaining"></span></div></td>
                    <td style="max-width:110px"><input type="number" min="0" step="0.01" class="form-control form-control-sm" :name="`lines[${i}][unit_price]`" x-model="l.unit_price"></td>
                    <td style="max-width:90px"><input type="number" min="0" step="0.001" class="form-control form-control-sm" :name="`lines[${i}][net_weight_kg]`" x-model="l.net_weight_kg"></td>
                    <td style="max-width:90px"><input type="number" min="0" step="0.001" class="form-control form-control-sm" :name="`lines[${i}][gross_weight_kg]`" x-model="l.gross_weight_kg"></td>
                    <td class="align-middle text-muted-sm" x-text="(chosenPi?.currency||'USD') + ' ' + ((parseFloat(l.unit_price||0)*parseInt(l.quantity||0)).toFixed(2))"></td>
                  </tr>
                </template>
              </tbody>
            </table>
            <div class="row g-2">
              <div class="col-md-3"><label class="form-label">Freight</label><input type="number" min="0" step="0.01" name="freight" class="form-control form-control-sm" x-model="form.freight" placeholder="0.00"></div>
              <div class="col-md-3"><label class="form-label">Insurance</label><input type="number" min="0" step="0.01" name="insurance" class="form-control form-control-sm" x-model="form.insurance" placeholder="0.00"></div>
              <div class="col-md-3"><label class="form-label">Port of loading</label><input type="text" name="port_of_loading" class="form-control form-control-sm" x-model="form.port_of_loading"></div>
              <div class="col-md-3"><label class="form-label">Port of discharge</label><input type="text" name="port_of_discharge" class="form-control form-control-sm" x-model="form.port_of_discharge"></div>
            </div>
            <div class="d-flex justify-content-end mt-2"><div class="text-end"><div class="text-muted-sm">CI total</div><div class="fw-bold fs-5" x-text="(chosenPi?.currency||'USD') + ' ' + grandTotal"></div></div></div>
            <div class="mt-2"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control form-control-sm" rows="2" x-model="form.remarks"></textarea></div>
          </div>
          <div x-show="form.proforma_invoice_id && !form.lines.length" class="text-muted-sm py-3">This PI is fully invoiced — nothing left to invoice.</div>
        </div>
        <div class="modal-footer">
          <div class="text-danger small me-auto" x-show="anyOver"><i class="bi bi-exclamation-triangle me-1"></i>Some lines exceed the remaining quantity.</div>
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="showAddModal=false">Cancel</button>
          <button type="button" class="btn btn-outline-primary btn-sm" @click="submitForm('draft')" :disabled="!form.lines.length || anyOver"><i class="bi bi-floppy me-1"></i>Save Draft</button>
          <button type="button" class="btn btn-primary btn-sm" @click="submitForm('pending_approval')" :disabled="!form.lines.length || anyOver"><i class="bi bi-send me-1"></i>Raise &amp; Send</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false" x-cloak></div>
