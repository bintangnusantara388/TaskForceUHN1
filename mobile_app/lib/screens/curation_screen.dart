// lib/screens/curation_screen.dart
import 'package:flutter/material.dart';
import '../models/reputation_entry.dart';
import '../services/api_service.dart';

class CurationScreen extends StatefulWidget {
  const CurationScreen({super.key});

  @override
  State<CurationScreen> createState() => _CurationScreenState();
}

class _CurationScreenState extends State<CurationScreen> {
  late Future<List<ReputationEntry>> _curationFuture;

  @override
  void initState() {
    super.initState();
    _loadCurationList();
  }

  void _loadCurationList() {
    setState(() {
      _curationFuture = ApiService.getEntries(
        screeningStatus: 'SIAP_DIOLAH',
      );
    });
  }

  void _showCurationDialog(ReputationEntry item) {
    final urlController = TextEditingController(text: item.commPublishedUrl ?? '');
    String status = item.commStatus;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Kurasi Materi: #${item.id}',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 6),
              Text(item.title, style: const TextStyle(fontSize: 12, color: Colors.black87)),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: status,
                decoration: const InputDecoration(labelText: 'Status Pengolahan Komunikasi', border: OutlineInputBorder()),
                items: const [
                  DropdownMenuItem(value: 'BELUM_DIOLAH', child: Text('Belum Diolah')),
                  DropdownMenuItem(value: 'SEDANG_DIOLAH', child: Text('Sedang Diolah (Penyusunan Rilis)')),
                  DropdownMenuItem(value: 'SUDAH_TERBIT', child: Text('Sudah Terbit Media')),
                ],
                onChanged: (val) => status = val!,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: urlController,
                decoration: const InputDecoration(
                  labelText: 'Tautan Publikasi Hasil (URL)',
                  hintText: 'https://harkatnegeri.ac.id/berita/...',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () async {
                    Navigator.pop(context);
                    await ApiService.updateKomunikasi(
                      item.id,
                      status,
                      urlController.text.trim(),
                      'Diperbarui via aplikasi mobile Komunikasi',
                    );
                    _loadCurationList();
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF8B0020), foregroundColor: Colors.white),
                  child: const Text('Simpan Status Kurasi'),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Meja Kurasi Komunikasi'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadCurationList),
        ],
      ),
      body: FutureBuilder<List<ReputationEntry>>(
        future: _curationFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          final list = snapshot.data ?? [];
          if (list.isEmpty) {
            return const Center(child: Text('Tidak ada antrean data siap olah.', style: TextStyle(color: Colors.black45)));
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: list.length,
            itemBuilder: (context, index) {
              final item = list[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: ListTile(
                  title: Text(item.title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 4),
                      Text(item.shortDescription, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11)),
                      const SizedBox(height: 6),
                      Text(
                        'Status: ${item.commStatus}',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: item.commStatus == 'SUDAH_TERBIT' ? Colors.green : const Color(0xFF8B0020),
                        ),
                      ),
                    ],
                  ),
                  trailing: ElevatedButton(
                    onPressed: () => _showCurationDialog(item),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFFFF1F3),
                      foregroundColor: const Color(0xFF8B0020),
                      elevation: 0,
                      visualDensity: VisualDensity.compact,
                    ),
                    child: const Text('Kelola', style: TextStyle(fontSize: 11)),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
