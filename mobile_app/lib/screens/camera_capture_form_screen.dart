// lib/screens/camera_capture_form_screen.dart
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';

class CameraCaptureFormScreen extends StatefulWidget {
  const CameraCaptureFormScreen({super.key});

  @override
  State<CameraCaptureFormScreen> createState() => _CameraCaptureFormScreenState();
}

class _CameraCaptureFormScreenState extends State<CameraCaptureFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final ImagePicker _picker = ImagePicker();
  File? _selectedImage;

  String _unitCode = 'KEMAHASISWAAN';
  String _periodId = '2026-10';
  String _tierType = 'SIGNATURE';
  String _nameConsent = 'YA';
  bool _isSubmitting = false;

  final _titleController = TextEditingController();
  final _shortDescController = TextEditingController();
  final _externalUrlController = TextEditingController();

  // Field spesifik Kemahasiswaan
  final _studentNameController = TextEditingController();
  final _studentNimController = TextEditingController();
  final _prodiController = TextEditingController();
  final _cohortController = TextEditingController(text: '2023');
  final _eventNameController = TextEditingController();
  final _organizerController = TextEditingController();
  final _locationController = TextEditingController();
  String _eventRank = 'Juara 1';
  String _eventLevel = 'Nasional';
  String _eventCategory = 'AKADEMIK';

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picked = await _picker.pickImage(source: source, imageQuality: 85);
      if (picked != null) {
        setState(() {
          _selectedImage = File(picked.path);
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal mengambil gambar: $e')),
      );
    }
  }

  int _countSentences(String text) {
    if (text.trim().isEmpty) return 0;
    final clean = text.replaceAll(RegExp(r'\s+'), ' ').trim();
    final parts = clean.split(RegExp(r'[.!?]+')).where((s) => s.trim().isNotEmpty).toList();
    return parts.length;
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) return;

    final sentences = _countSentences(_shortDescController.text);
    if (sentences > 3) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Deskripsi maksimal 3 kalimat (terdeteksi: $sentences kalimat).'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final fields = <String, String>{
        'unit_code': _unitCode,
        'period_id': _periodId,
        'tier_type': _tierType,
        'title': _titleController.text.trim(),
        'short_description': _shortDescController.text.trim(),
        'name_publication_consent': _nameConsent,
        'external_url': _externalUrlController.text.trim(),
        'screening_status': 'DRAFT',
      };

      if (_unitCode == 'KEMAHASISWAAN') {
        fields['student_name'] = _studentNameController.text.trim();
        fields['student_nim'] = _studentNimController.text.trim();
        fields['prodi'] = _prodiController.text.trim();
        fields['cohort_year'] = _cohortController.text.trim();
        fields['event_name'] = _eventNameController.text.trim();
        fields['organizer'] = _organizerController.text.trim();
        fields['event_location'] = _locationController.text.trim();
        fields['event_date'] = DateTime.now().toIso8601String().substring(0, 10);
        fields['achievement_rank'] = _eventRank;
        fields['level'] = _eventLevel;
        fields['category'] = _eventCategory;
      }

      final success = await ApiService.uploadEntryWithPhoto(
        fields: fields,
        imageFile: _selectedImage,
      );

      if (success) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✓ Capaian berhasil disetor ke server ERP!'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final sentences = _countSentences(_shortDescController.text);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Input Capaian Mobile'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Preview & Capture Kamera
            Container(
              height: 180,
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.grey.shade300, style: BorderStyle.solid),
              ),
              child: _selectedImage != null
                  ? ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          Image.file(_selectedImage!, fit: BoxFit.cover),
                          Positioned(
                            top: 8,
                            right: 8,
                            child: IconButton(
                              icon: const CircleAvatar(
                                backgroundColor: Colors.black54,
                                child: Icon(Icons.close, color: Colors.white, size: 18),
                              ),
                              onPressed: () => setState(() => _selectedImage = null),
                            ),
                          ),
                        ],
                      ),
                    )
                  : Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.add_a_photo, size: 40, color: Colors.black45),
                        const SizedBox(height: 8),
                        const Text(
                          'Ambil Foto Sertifikat / Dokumentasi',
                          style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.black87),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            ElevatedButton.icon(
                              onPressed: () => _pickImage(ImageSource.camera),
                              icon: const Icon(Icons.camera_alt, size: 16),
                              label: const Text('Kamera HP'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF0F172A),
                                foregroundColor: Colors.white,
                              ),
                            ),
                            const SizedBox(width: 8),
                            OutlinedButton.icon(
                              onPressed: () => _pickImage(ImageSource.gallery),
                              icon: const Icon(Icons.photo_library, size: 16),
                              label: const Text('Galeri'),
                            ),
                          ],
                        ),
                      ],
                    ),
            ),

            const SizedBox(height: 20),

            // Pilihan Unit & Status Tier
            DropdownButtonFormField<String>(
              value: _unitCode,
              decoration: const InputDecoration(
                labelText: 'Unit Kerja',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'KEMAHASISWAAN', child: Text('Kemahasiswaan (Juara Lomba)')),
                DropdownMenuItem(value: 'PPM', child: Text('PPM (Paten / HKI / Riset)')),
                DropdownMenuItem(value: 'PSDM', child: Text('PSDM (Capaian Pegawai)')),
                DropdownMenuItem(value: 'ALUMNI_KARIR', child: Text('Alumni Karir & Bisnis')),
              ],
              onChanged: (val) => setState(() => _unitCode = val!),
            ),

            const SizedBox(height: 12),

            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _tierType,
                    decoration: const InputDecoration(
                      labelText: 'Status Tier',
                      border: OutlineInputBorder(),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'SIGNATURE', child: Text('★ Signature (Unggulan)')),
                      DropdownMenuItem(value: 'REGULER', child: Text('Reguler')),
                    ],
                    onChanged: (val) => setState(() => _tierType = val!),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _periodId,
                    decoration: const InputDecoration(
                      labelText: 'Bulan Pelaporan',
                      border: OutlineInputBorder(),
                    ),
                    items: const [
                      DropdownMenuItem(value: '2026-10', child: Text('Oktober 2026')),
                      DropdownMenuItem(value: '2026-11', child: Text('November 2026')),
                      DropdownMenuItem(value: '2026-12', child: Text('Desember 2026')),
                    ],
                    onChanged: (val) => setState(() => _periodId = val!),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Judul Capaian
            TextFormField(
              controller: _titleController,
              decoration: const InputDecoration(
                labelText: 'Judul Capaian Institusi *',
                hintText: 'Contoh: Juara 1 Medali Emas Lomba Robotika Nasional',
                border: OutlineInputBorder(),
              ),
              validator: (val) => val == null || val.trim().isEmpty ? 'Wajib diisi' : null,
            ),

            const SizedBox(height: 16),

            // Deskripsi Singkat (3 Kalimat)
            TextFormField(
              controller: _shortDescController,
              maxLines: 3,
              onChanged: (_) => setState(() {}),
              decoration: InputDecoration(
                labelText: 'Deskripsi Singkat (Bahasa Awam) *',
                hintText: 'Maksimal 3 kalimat untuk bahan olahan rilis Komunikasi.',
                border: const OutlineInputBorder(),
                helperText: '$sentences / 3 Kalimat Terdeteksi',
                helperStyle: TextStyle(
                  color: sentences > 3 ? Colors.red : Colors.grey.shade600,
                  fontWeight: FontWeight.bold,
                ),
              ),
              validator: (val) => val == null || val.trim().isEmpty ? 'Wajib diisi' : null,
            ),

            const SizedBox(height: 16),

            // Form Spesifik Kemahasiswaan
            if (_unitCode == 'KEMAHASISWAAN') ...[
              const Text(
                'Data Mahasiswa & Prestasi',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.black87),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    flex: 3,
                    child: TextFormField(
                      controller: _studentNameController,
                      decoration: const InputDecoration(labelText: 'Nama Mahasiswa *', border: OutlineInputBorder()),
                      validator: (val) => val == null || val.isEmpty ? 'Wajib diisi' : null,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    flex: 2,
                    child: TextFormField(
                      controller: _studentNimController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'NIM (Angka) *', border: OutlineInputBorder()),
                      validator: (val) {
                        if (val == null || val.isEmpty) return 'Wajib diisi';
                        if (!RegExp(r'^[0-9]+$').hasMatch(val)) return 'Hanya angka';
                        return null;
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _prodiController,
                      decoration: const InputDecoration(labelText: 'Prodi *', border: OutlineInputBorder()),
                      validator: (val) => val == null || val.isEmpty ? 'Wajib diisi' : null,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _eventRank,
                      decoration: const InputDecoration(labelText: 'Juara *', border: OutlineInputBorder()),
                      items: const [
                        DropdownMenuItem(value: 'Juara 1', child: Text('Juara 1 / Emas')),
                        DropdownMenuItem(value: 'Juara 2', child: Text('Juara 2 / Perak')),
                        DropdownMenuItem(value: 'Juara 3', child: Text('Juara 3 / Perunggu')),
                        DropdownMenuItem(value: 'Harapan', child: Text('Harapan')),
                      ],
                      onChanged: (val) => setState(() => _eventRank = val!),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _eventNameController,
                decoration: const InputDecoration(labelText: 'Nama Kegiatan / Lomba *', border: OutlineInputBorder()),
                validator: (val) => val == null || val.isEmpty ? 'Wajib diisi' : null,
              ),
            ],

            const SizedBox(height: 16),

            // Card Privasi UU PDP
            Card(
              color: Colors.amber.shade50,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
                side: BorderSide(color: Colors.amber.shade200),
              ),
              child: Padding(
                padding: const EdgeInsets.all(12.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Persetujuan Publikasi Nama (UU PDP)',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Apakah nama subjek diperbolehkan tampil terbuka di media massa & media sosial kampus?',
                      style: TextStyle(fontSize: 11, color: Colors.black54),
                    ),
                    Row(
                      children: [
                        Radio<String>(
                          value: 'YA',
                          groupValue: _nameConsent,
                          onChanged: (val) => setState(() => _nameConsent = val!),
                        ),
                        const Text('Ya (Boleh Publikasi)', style: TextStyle(fontSize: 12)),
                        const SizedBox(width: 16),
                        Radio<String>(
                          value: 'TIDAK',
                          groupValue: _nameConsent,
                          onChanged: (val) => setState(() => _nameConsent = val!),
                        ),
                        const Text('Tidak (Privat/Restricted)', style: TextStyle(fontSize: 12)),
                      ],
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 24),

            // Tombol Simpan
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _submitForm,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF8B0020),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: _isSubmitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text(
                        'SIMPAN CAPAIAN KE SERVER ERP',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
