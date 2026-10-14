// lib/main.dart
import 'package:flutter/material.dart';
import 'screens/dashboard_screen.dart';
import 'screens/entry_list_screen.dart';
import 'screens/camera_capture_form_screen.dart';
import 'screens/curation_screen.dart';
import 'services/api_service.dart';

void main() {
  runApp(const UhnTaskforceMobileApp());
}

class UhnTaskforceMobileApp extends StatelessWidget {
  const UhnTaskforceMobileApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ERP Reputasi Institusi - Universitas Harkat Negeri',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF8B0020),
          primary: const Color(0xFF8B0020),
          secondary: const Color(0xFFE11D48),
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF680015),
          foregroundColor: Colors.white,
          elevation: 2,
        ),
      ),
      home: const MainNavigationShell(),
    );
  }
}

class MainNavigationShell extends StatefulWidget {
  const MainNavigationShell({super.key});

  @override
  State<MainNavigationShell> createState() => _MainNavigationShellState();
}

class _MainNavigationShellState extends State<MainNavigationShell> {
  int _currentIndex = 0;

  final List<Widget> _screens = const [
    DashboardScreen(),
    EntryListScreen(),
    CurationScreen(),
  ];

  void _showRoleSwitcher() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Padding(
                padding: EdgeInsets.all(16.0),
                child: Text(
                  'Ganti Role Aktif (Simulasi Mobile)',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                ),
              ),
              ListTile(
                leading: const Icon(Icons.admin_panel_settings),
                title: const Text('Admin Task Force'),
                subtitle: const Text('Dr. Hendra Gunawan, S.T., M.T.'),
                selected: ApiService.activeUserKey == 'admin_tf',
                onTap: () {
                  setState(() => ApiService.activeUserKey = 'admin_tf');
                  Navigator.pop(context);
                },
              ),
              ListTile(
                leading: const Icon(Icons.school),
                title: const Text('PIC Kemahasiswaan'),
                subtitle: const Text('Rizky Kurniawan, S.Pd.'),
                selected: ApiService.activeUserKey == 'pic_kemhs',
                onTap: () {
                  setState(() => ApiService.activeUserKey = 'pic_kemhs');
                  Navigator.pop(context);
                },
              ),
              ListTile(
                leading: const Icon(Icons.campaign),
                title: const Text('Bagian Komunikasi & Promosi'),
                subtitle: const Text('Citra Lestari, S.I.Kom.'),
                selected: ApiService.activeUserKey == 'komunikasi',
                onTap: () {
                  setState(() => ApiService.activeUserKey = 'komunikasi');
                  Navigator.pop(context);
                },
              ),
              ListTile(
                leading: const Icon(Icons.visibility),
                title: const Text('Viewer / Pimpinan Kampus'),
                subtitle: const Text('Prof. Dr. H. Ahmad Sudrajat, M.Si.'),
                selected: ApiService.activeUserKey == 'viewer',
                onTap: () {
                  setState(() => ApiService.activeUserKey = 'viewer');
                  Navigator.pop(context);
                },
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
      body: _screens[_currentIndex],
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => const CameraCaptureFormScreen(),
            ),
          );
        },
        backgroundColor: const Color(0xFF8B0020),
        foregroundColor: Colors.white,
        tooltip: 'Input Capaian Cepat',
        child: const Icon(Icons.add_a_photo),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) {
          if (index == 3) {
            _showRoleSwitcher();
          } else {
            setState(() => _currentIndex = index);
          }
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard),
            label: 'Beranda',
          ),
          NavigationDestination(
            icon: Icon(Icons.format_list_bulleted_outlined),
            selectedIcon: Icon(Icons.format_list_bulleted),
            label: 'Capaian',
          ),
          NavigationDestination(
            icon: Icon(Icons.edit_note_outlined),
            selectedIcon: Icon(Icons.edit_note),
            label: 'Kurasi',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Akun',
          ),
        ],
      ),
    );
  }
}
