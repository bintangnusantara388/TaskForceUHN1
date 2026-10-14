<?php
// views/components/ai_narrative_widget.php
// Komponen AI Generator Narasi 3 Kalimat Bahasa Awam
$widgetUnitCode = $widgetUnitCode ?? ($entry['unit_code'] ?? ($unitCode ?? 'PPM'));
?>

<div class="bg-gradient-to-br from-rose-50/80 via-white to-amber-50/60 border border-rose-200/90 rounded-2xl p-4 space-y-3 shadow-sm my-3 relative overflow-hidden transition-all duration-200">
    <!-- Dekorasi watermark icon AI -->
    <div class="absolute -right-3 -top-3 text-rose-200/30 select-none pointer-events-none">
        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
    </div>

    <!-- Bar Header AI Assistant -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 relative z-10">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-xl bg-gradient-to-tr from-uhn-900 to-rose-600 text-white flex items-center justify-center font-black text-xs shadow-sm shadow-rose-900/30">
                ✨
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-black text-uhn-950 uppercase tracking-wide">
                        Teknologi AI: Generator Narasi 3 Kalimat
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-uhn-900 border border-rose-200">
                        Otomatis WURI
                    </span>
                </div>
                <p class="text-[11px] text-slate-500">
                    Merangkum otomatis input formulir di atas menjadi tepat 3 kalimat bahasa awam standar rilis pers &amp; borang institusi.
                </p>
            </div>
        </div>

        <!-- Tombol Aksi AI Utama -->
        <div class="flex items-center gap-2 self-start sm:self-auto flex-shrink-0">
            <button type="button" id="btnAiGenerate" onclick="executeAiNarrativeGeneration('<?= htmlspecialchars($widgetUnitCode) ?>')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-uhn-900 via-rose-800 to-uhn-800 hover:from-uhn-950 hover:to-rose-900 text-white rounded-xl text-xs font-black shadow-md shadow-rose-950/20 active:scale-95 transition-all">
                <svg id="aiIconSpark" class="w-4 h-4 text-amber-300 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                <span id="aiBtnText">✨ Buat Narasi Otomatis (AI)</span>
            </button>
        </div>
    </div>

    <!-- Opsi Gaya Bahasa (Tone) & Feedback Status -->
    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-rose-100/80 relative z-10 text-xs">
        <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-[11px] font-bold text-slate-500">Gaya Bahasa:</span>
            
            <label class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-rose-200 bg-white hover:bg-rose-50 cursor-pointer text-[11px] font-semibold text-slate-700 has-[:checked]:bg-uhn-900 has-[:checked]:text-white has-[:checked]:border-uhn-900 transition-colors">
                <input type="radio" name="ai_tone_choice" value="STANDAR" checked class="hidden" onchange="window.currentAiTone = this.value">
                <span>🏛️ Standar WURI</span>
            </label>

            <label class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-rose-200 bg-white hover:bg-rose-50 cursor-pointer text-[11px] font-semibold text-slate-700 has-[:checked]:bg-uhn-900 has-[:checked]:text-white has-[:checked]:border-uhn-900 transition-colors">
                <input type="radio" name="ai_tone_choice" value="MEDIA" class="hidden" onchange="window.currentAiTone = this.value">
                <span>📰 Rilis Berita</span>
            </label>

            <label class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-rose-200 bg-white hover:bg-rose-50 cursor-pointer text-[11px] font-semibold text-slate-700 has-[:checked]:bg-uhn-900 has-[:checked]:text-white has-[:checked]:border-uhn-900 transition-colors">
                <input type="radio" name="ai_tone_choice" value="INSPIRATIF" class="hidden" onchange="window.currentAiTone = this.value">
                <span>💡 Inspiratif</span>
            </label>
        </div>

        <div id="aiStatusPill" class="hidden items-center gap-2">
            <span id="aiStatusBadge" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                ✓ Siap
            </span>
            <button type="button" id="btnUndoAi" onclick="undoAiNarrative()" class="text-[11px] text-slate-500 hover:text-rose-700 underline font-medium">
                Kembalikan Teks Awal
            </button>
        </div>
    </div>
</div>

<script>
// Global AI state untuk form ini
window.previousTextBeforeAi = null;
window.currentAiTone = 'STANDAR';

function executeAiNarrativeGeneration(unitCode) {
    const titleInput = document.querySelector('[name="title"]');
    const title = titleInput ? titleInput.value.trim() : '';

    if (!title) {
        alert('Mohon isi minimal "Judul Capaian" terlebih dahulu agar AI dapat merumuskan konteks narasi dengan akurat.');
        if (titleInput) titleInput.focus();
        return;
    }

    const shortDescTextarea = document.getElementById('shortDescInput');
    if (!shortDescTextarea) return;

    // Simpan teks awal untuk fitur Undo
    if (shortDescTextarea.value.trim() && window.previousTextBeforeAi === null) {
        window.previousTextBeforeAi = shortDescTextarea.value;
    }

    // UI Loading state
    const btn = document.getElementById('btnAiGenerate');
    const btnText = document.getElementById('aiBtnText');
    const icon = document.getElementById('aiIconSpark');
    const statusPill = document.getElementById('aiStatusPill');
    const statusBadge = document.getElementById('aiStatusBadge');

    const originalBtnHtml = btnText.innerHTML;
    btn.disabled = true;
    btn.classList.add('opacity-80', 'cursor-not-allowed');
    btnText.innerHTML = '<span class="inline-flex items-center gap-2"><svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> AI Menyusun 3 Kalimat...</span>';

    // Kumpulkan payload spesifik unit
    const payload = collectUnitFormPayload(unitCode);
    payload.tone = window.currentAiTone || 'STANDAR';

    fetch('/index.php?action=ai_generate_narrative', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.classList.remove('opacity-80', 'cursor-not-allowed');
        btnText.innerHTML = originalBtnHtml;

        if (data && data.success && data.narrative) {
            shortDescTextarea.value = data.narrative;
            
            // Trigger validasi hitungan kalimat
            checkSentenceCount(shortDescTextarea);

            // Efek visual glow
            shortDescTextarea.classList.add('ring-4', 'ring-rose-300', 'bg-rose-50/30');
            setTimeout(() => {
                shortDescTextarea.classList.remove('ring-4', 'ring-rose-300', 'bg-rose-50/30');
            }, 1200);

            // Tampilkan feedback pill
            if (statusPill && statusBadge) {
                statusPill.classList.remove('hidden');
                statusPill.classList.add('flex');
                statusBadge.innerHTML = '✨ 3 Kalimat Selesai (' + (data.provider_label || 'AI') + ')';
            }
        } else {
            alert('Gagal menghasilkan narasi: ' + (data.message || 'Terjadi kesalahan sistem'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.classList.remove('opacity-80', 'cursor-not-allowed');
        btnText.innerHTML = originalBtnHtml;
        console.error('AI Generation Error:', err);
        alert('Terjadi kendala saat menghubungi layanan AI. Silakan coba kembali.');
    });
}

function undoAiNarrative() {
    const shortDescTextarea = document.getElementById('shortDescInput');
    if (shortDescTextarea && window.previousTextBeforeAi !== null) {
        shortDescTextarea.value = window.previousTextBeforeAi;
        checkSentenceCount(shortDescTextarea);
        const statusPill = document.getElementById('aiStatusPill');
        if (statusPill) statusPill.classList.add('hidden');
        window.previousTextBeforeAi = null;
    }
}

function collectUnitFormPayload(unitCode) {
    const payload = {
        unit_code: unitCode,
        title: document.querySelector('[name="title"]')?.value || '',
        tier_type: document.querySelector('[name="tier_type"]:checked')?.value || 'REGULER',
        name_publication_consent: document.querySelector('[name="name_publication_consent"]:checked')?.value || 'YA',
        notes: document.querySelector('[name="notes"]')?.value || '',
        external_url: document.querySelector('[name="external_url"]')?.value || ''
    };

    if (unitCode === 'PPM') {
        payload.ki_type = document.querySelector('[name="ki_type"]')?.value || '';
        payload.registration_number = document.querySelector('[name="registration_number"]')?.value || '';
        payload.ki_status = document.querySelector('[name="ki_status"]')?.value || '';
        const invs = [];
        document.querySelectorAll('[name="inventor_name[]"]').forEach(el => {
            if (el.value.trim()) invs.push(el.value.trim());
        });
        payload.inventor_name = invs;
    } else if (unitCode === 'PSDM') {
        payload.employee_name = document.querySelector('[name="employee_name"]')?.value || '';
        payload.employee_id = document.querySelector('[name="employee_id"]')?.value || '';
        payload.unit_or_prodi = document.querySelector('[name="unit_or_prodi"]')?.value || '';
        payload.achievement_type = document.querySelector('[name="achievement_type"]')?.value || '';
        payload.issuer_organizer = document.querySelector('[name="issuer_organizer"]')?.value || '';
        payload.level = document.querySelector('[name="level"]')?.value || '';
    } else if (unitCode === 'KEMAHASISWAAN') {
        payload.student_name = document.querySelector('[name="student_name"]')?.value || '';
        payload.student_nim = document.querySelector('[name="student_nim"]')?.value || '';
        payload.prodi = document.querySelector('[name="prodi"]')?.value || '';
        payload.cohort_year = document.querySelector('[name="cohort_year"]')?.value || '';
        payload.event_name = document.querySelector('[name="event_name"]')?.value || '';
        payload.achievement_rank = document.querySelector('[name="achievement_rank"]')?.value || '';
        payload.level = document.querySelector('[name="level"]')?.value || '';
        payload.organizer = document.querySelector('[name="organizer"]')?.value || '';
        payload.advisor_name = document.querySelector('[name="advisor_name"]')?.value || '';
    } else if (unitCode === 'ALUMNI_KARIR') {
        payload.alumni_name = document.querySelector('[name="alumni_name"]')?.value || '';
        payload.graduation_year = document.querySelector('[name="graduation_year"]')?.value || '';
        payload.prodi = document.querySelector('[name="prodi"]')?.value || '';
        payload.achievement_type = document.querySelector('[name="achievement_type"]')?.value || '';
        payload.company_or_institution = document.querySelector('[name="company_or_institution"]')?.value || '';
        payload.position = document.querySelector('[name="position"]')?.value || '';
        payload.testimonial = document.querySelector('[name="testimonial"]')?.value || '';
    }

    return payload;
}

// Sinkronisasi fungsi hitungan kalimat dengan proteksi singkatan & gelar akademik
function checkSentenceCount(el) {
    if (!el) return;
    const text = el.value.trim();
    const badge = document.getElementById('sentenceCountBadge');
    if (!badge) return;

    if (!text) {
        badge.innerText = '0 / 3 Kalimat';
        badge.className = 'text-xs font-bold text-slate-500';
        return;
    }

    // Lindungi singkatan umum dan gelar akademik dari pembagian kalimat
    let clean = text
        .replace(/\b(Dr|Prof|Ir|Drs|Dra|apt|Ns|S\.T|M\.T|S\.Kom|M\.Kom|S\.E|M\.M|S\.Si|M\.Si|S\.Pd|M\.Pd|M\.B\.A|Ph\.D|B\.Sc|M\.Sc|H|Hj|dkk|dll|dsb|dst|No)\./gi, '$1__DOT__')
        .replace(/\b([A-Z])\./g, '$1__DOT__');

    const matches = clean.match(/[^.!?]+[.!?]+(?:\s+|$)/g);
    const count = matches ? matches.length : 1;

    badge.innerText = count + ' / 3 Kalimat';
    if (count > 3) {
        badge.className = 'text-xs font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded';
    } else if (count === 3) {
        badge.className = 'text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded ring-1 ring-emerald-300';
    } else {
        badge.className = 'text-xs font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded';
    }
}
</script>
