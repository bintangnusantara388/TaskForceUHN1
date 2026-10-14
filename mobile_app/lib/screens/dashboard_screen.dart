// lib/screens/dashboard_screen.dart
import 'package:flutter/material.dart';
import '../models/dashboard_summary.dart';
import '../services/api_service.dart';
import 'camera_capture_form_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late Future<DashboardData> _dashboardFuture;
  String _selectedPeriod = '2026-10';

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  void _loadDashboard() {
    setState(() {
      _dashboardFuture = ApiService.getDashboard(periodId: _selectedPeriod);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: const [
            Text(
              'UNIVERSITAS HARKAT NEGERI',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.w900, letterSpacing: 0.5),
            ),
            Text(
              'Task Force Reputasi & Branding Institusi',
              style: TextStyle(fontSize: 11, color: Colors.amberAccent),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadDashboard,
          ),
        ],
      ),
      body: FutureBuilder<DashboardData>(
        future: _dashboardFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.cloud_off, size: 50, color: Colors.redAccent),
                    const SizedBox(height: 12),
                    Text(
                      'Tidak dapat terhubung ke server ERP:\n${snapshot.error}',
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 12, color: Colors.black54),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: _loadDashboard,
                      child: const Text('Coba Lagi'),
                    ),
                  ],
                ),
              ),
            );
          }

          final data = snapshot.data!;
          return RefreshIndicator(
            onRefresh: () async => _loadDashboard(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Banner Hero Mobile
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF8B0020), Color(0xFFA30029)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF8B0020).withOpacity(0.25),
                        blurRadius: 14,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Chip(
                            label: Text(
                              data.periodName,
                              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                            backgroundColor: const Color(0xFF680015),
                            side: BorderSide.none,
                            visualDensity: VisualDensity.compact,
                          ),
                          Text(
                            'WURI 2027 Ready',
                            style: TextStyle(fontSize: 11, color: Colors.pink.shade100, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Target Kepatuhan Capaian',
                        style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Minimal 1 data Signature per unit setiap bulan kalender pelaporan.',
                        style: TextStyle(color: Colors.pink.shade100, fontSize: 12),
                      ),
                      const Divider(color: Colors.white24, height: 24),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          _buildStatItem('Total Capaian', '${data.totalEntries}', Colors.white),
                          _buildStatItem(
                            'Target Signature',
                            '${data.signatureFulfilledCount}/${data.totalUnits}',
                            data.signatureFulfilledCount >= data.totalUnits ? const Color(0xFF10B981) : Colors.amberAccent,
                          ),
                          _buildStatItem('Perlu Kurasi', '${data.pendingCommCount}', Colors.amberAccent),
                        ],
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 20),

                // Tombol Akses Kamera Cepat di Lapangan
                Card(
                  elevation: 0,
                  color: Colors.amber.shade50,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                    side: BorderSide(color: Colors.amber.shade200),
                  ),
                  child: ListTile(
                    leading: const CircleAvatar(
                      backgroundColor: Colors.amber,
                      child: Icon(Icons.camera_alt, color: Colors.black87),
                    ),
                    title: const Text(
                      'Foto & Setor Capaian Sekarang',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    subtitle: const Text(
                      'Ambil foto sertifikat/piagam langsung dari kamera HP.',
                      style: TextStyle(fontSize: 11),
                    ),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const CameraCaptureFormScreen(),
                        ),
                      ).then((_) => _loadDashboard());
                    },
                  ),
                ),

                const SizedBox(height: 20),

                // Heading Progress Meter
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: const [
                    Text(
                      'PROGRESS METER PER UNIT',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black54, letterSpacing: 0.5),
                    ),
                    Text('Status Kuota', style: TextStyle(fontSize: 11, color: Colors.black45)),
                  ],
                ),
                const SizedBox(height: 10),

                // Daftar Kartu Progress Meter Tiap Unit
                ...data.progressList.map((unit) => _buildUnitCard(unit)).toList(),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildStatItem(String label, String value, Color valueColor) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: valueColor)),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(fontSize: 10, color: Colors.white70)),
      ],
    );
  }

  Widget _buildUnitCard(UnitProgress unit) {
    final isFulfilled = unit.isSignatureFulfilled;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(14.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFF8B0020),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        unit.unitCode,
                        style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      unit.unitName,
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: isFulfilled ? Colors.green.shade50 : Colors.red.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: BorderSide(
                      color: isFulfilled ? Colors.green.shade300 : Colors.red.shade300,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        isFulfilled ? Icons.check_circle : Icons.cancel,
                        size: 13,
                        color: isFulfilled ? Colors.green.shade700 : Colors.red.shade700,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        isFulfilled ? 'Signature Terpenuhi' : 'Belum Ada Signature',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: isFulfilled ? Colors.green.shade800 : Colors.red.shade800,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            LinearProgressIndicator(
              value: isFulfilled ? 1.0 : (unit.totalEntries > 0 ? 0.5 : 0.0),
              backgroundColor: Colors.grey.shade200,
              valueColor: AlwaysStoppedAnimation<Color>(
                isFulfilled ? Colors.green : Colors.amber,
              ),
              minHeight: 6,
              borderRadius: BorderRadius.circular(4),
            ),
            const SizedBox(height: 10),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Total Masuk: ${unit.totalEntries}', style: const TextStyle(fontSize: 11, color: Colors.black54)),
                Text('Siap Diolah: ${unit.siapDiolah}', style: const TextStyle(fontSize: 11, color: Colors.black54)),
                Text(
                  'Pending Kurasi: ${unit.belumDiolahKom}',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: unit.belumDiolahKom > 0 ? Colors.purple.shade700 : Colors.black45,
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
