// lib/screens/entry_list_screen.dart
import 'package:flutter/material.dart';
import '../models/reputation_entry.dart';
import '../services/api_service.dart';

class EntryListScreen extends StatefulWidget {
  const EntryListScreen({super.key});

  @override
  State<EntryListScreen> createState() => _EntryListScreenState();
}

class _EntryListScreenState extends State<EntryListScreen> {
  late Future<List<ReputationEntry>> _entriesFuture;
  String? _selectedTier;
  String? _selectedScreening;
  String? _selectedConsent;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchEntries();
  }

  void _fetchEntries() {
    setState(() {
      _entriesFuture = ApiService.getEntries(
        tierType: _selectedTier,
        screeningStatus: _selectedScreening,
        consent: _selectedConsent,
        keyword: _searchController.text.trim(),
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Inventarisasi Capaian'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchEntries,
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Cari judul capaian...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          _fetchEntries();
                        },
                      )
                    : null,
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onSubmitted: (_) => _fetchEntries(),
            ),
          ),

          // Filter Chips Horizontal Scroll
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              children: [
                FilterChip(
                  label: const Text('Semua'),
                  selected: _selectedTier == null && _selectedScreening == null && _selectedConsent == null,
                  onSelected: (_) {
                    setState(() {
                      _selectedTier = null;
                      _selectedScreening = null;
                      _selectedConsent = null;
                    });
                    _fetchEntries();
                  },
                ),
                const SizedBox(width: 8),
                FilterChip(
                  label: const Text('★ Signature'),
                  selected: _selectedTier == 'SIGNATURE',
                  onSelected: (val) {
                    setState(() => _selectedTier = val ? 'SIGNATURE' : null);
                    _fetchEntries();
                  },
                ),
                const SizedBox(width: 8),
                FilterChip(
                  label: const Text('✓ Siap Diolah'),
                  selected: _selectedScreening == 'SIAP_DIOLAH',
                  onSelected: (val) {
                    setState(() => _selectedScreening = val ? 'SIAP_DIOLAH' : null);
                    _fetchEntries();
                  },
                ),
                const SizedBox(width: 8),
                FilterChip(
                  label: const Text('⚠️ Restricted PII'),
                  selected: _selectedConsent == 'TIDAK',
                  selectedColor: Colors.red.shade100,
                  onSelected: (val) {
                    setState(() => _selectedConsent = val ? 'TIDAK' : null);
                    _fetchEntries();
                  },
                ),
              ],
            ),
          ),

          const Divider(height: 16),

          // List Items
          Expanded(
            child: FutureBuilder<List<ReputationEntry>>(
              future: _entriesFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (snapshot.hasError) {
                  return Center(
                    child: Text('Gagal memuat: ${snapshot.error}', style: const TextStyle(fontSize: 12)),
                  );
                }

                final entries = snapshot.data ?? [];
                if (entries.isEmpty) {
                  return const Center(
                    child: Text('Tidak ada data yang sesuai filter.', style: TextStyle(color: Colors.black45)),
                  );
                }

                return RefreshIndicator(
                  onRefresh: () async => _fetchEntries(),
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    itemCount: entries.length,
                    itemBuilder: (context, index) {
                      final item = entries[index];
                      return _buildEntryCard(item);
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEntryCard(ReputationEntry item) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(14.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header: ID, Unit, Tier
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Text('#${item.id}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Colors.amber)),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade200,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        item.unitCode,
                        style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: item.isSignature ? Colors.amber.shade100 : Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    item.isSignature ? '★ SIGNATURE' : 'REGULER',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      color: item.isSignature ? Colors.amber.shade900 : Colors.blue.shade900,
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 8),

            // Judul Capaian
            Text(
              item.title,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, height: 1.3),
            ),

            const SizedBox(height: 4),

            // Deskripsi Singkat
            Text(
              item.shortDescription,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(fontSize: 11, color: Colors.grey.shade700, height: 1.3),
            ),

            // UU PDP Warning Banner jika consent == TIDAK
            if (item.isRestricted) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(6),
                  border: Border.all(color: Colors.red.shade200),
                ),
                child: Row(
                  children: const [
                    Icon(Icons.lock, size: 12, color: Colors.red),
                    SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        'Restricted: Subjek TIDAK mengizinkan publikasi nama pribadi.',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.red),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            const SizedBox(height: 10),

            // Footer Status Screening & Aksi
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: item.isSiapDiolah ? Colors.green.shade50 : Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(4),
                        border: Border.all(
                          color: item.isSiapDiolah ? Colors.green.shade200 : Colors.grey.shade300,
                        ),
                      ),
                      child: Text(
                        item.isSiapDiolah ? '✓ Siap Diolah' : 'Draft Unit',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: item.isSiapDiolah ? Colors.green.shade800 : Colors.grey.shade700,
                        ),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      item.commStatus == 'SUDAH_TERBIT'
                          ? 'Sudah Terbit'
                          : (item.commStatus == 'SEDANG_DIOLAH' ? 'Sedang Diolah' : 'Belum Diolah'),
                      style: TextStyle(
                        fontSize: 10,
                        color: item.commStatus == 'SUDAH_TERBIT' ? Colors.blue.shade700 : Colors.grey.shade500,
                      ),
                    ),
                  ],
                ),
                TextButton(
                  onPressed: () async {
                    final toggled = await ApiService.toggleScreening(item.id);
                    if (toggled) _fetchEntries();
                  },
                  child: Text(
                    item.isSiapDiolah ? 'Tarik Draft' : 'Loloskan',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
