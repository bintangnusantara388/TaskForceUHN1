// lib/services/api_service.dart
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../models/dashboard_summary.dart';
import '../models/reputation_entry.dart';

class ApiService {
  // Ganti baseUrl sesuai IP server jika dijalankan di perangkat fisik (misal: http://192.168.1.10:8088)
  // Untuk Android Emulator default: http://10.0.2.2:8088
  static String baseUrl = 'http://localhost:8088';
  static String activeUserKey = 'admin_tf';

  /// Mendapatkan ringkasan dashboard dan progress meter
  static Future<DashboardData> getDashboard({String periodId = '2026-10'}) async {
    final uri = Uri.parse('$baseUrl/api.php?endpoint=dashboard&period_id=$periodId&user=$activeUserKey');
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return DashboardData.fromJson(json);
    } else {
      throw Exception('Gagal memuat dashboard: ${response.statusCode}');
    }
  }

  /// Mendapatkan daftar entri capaian
  static Future<List<ReputationEntry>> getEntries({
    String? periodId,
    String? unitCode,
    String? tierType,
    String? screeningStatus,
    String? commStatus,
    String? consent,
    String? keyword,
  }) async {
    final queryParams = {
      'endpoint': 'entries',
      'user': activeUserKey,
      if (periodId != null && periodId.isNotEmpty) 'period_id': periodId,
      if (unitCode != null && unitCode.isNotEmpty) 'unit_code': unitCode,
      if (tierType != null && tierType.isNotEmpty) 'tier_type': tierType,
      if (screeningStatus != null && screeningStatus.isNotEmpty) 'screening_status': screeningStatus,
      if (commStatus != null && commStatus.isNotEmpty) 'comm_status': commStatus,
      if (consent != null && consent.isNotEmpty) 'consent': consent,
      if (keyword != null && keyword.isNotEmpty) 'keyword': keyword,
    };

    final uri = Uri.parse('$baseUrl/api.php').replace(queryParameters: queryParams);
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      final list = json['data'] as List<dynamic>? ?? [];
      return list.map((e) => ReputationEntry.fromJson(e as Map<String, dynamic>)).toList();
    } else {
      throw Exception('Gagal memuat daftar entri: ${response.statusCode}');
    }
  }

  /// Toggle status screening unit (Draft <-> Siap Diolah)
  static Future<bool> toggleScreening(int id) async {
    final uri = Uri.parse('$baseUrl/api.php?endpoint=toggle_screening&id=$id&user=$activeUserKey');
    final response = await http.post(uri);
    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return json['success'] == true;
    }
    return false;
  }

  /// Update kurasi komunikasi (Sedang Diolah / Sudah Terbit)
  static Future<bool> updateKomunikasi(int id, String commStatus, String publishedUrl, String notes) async {
    final uri = Uri.parse('$baseUrl/api.php?endpoint=update_komunikasi&id=$id&user=$activeUserKey');
    final response = await http.post(uri, body: {
      'comm_status': commStatus,
      'comm_published_url': publishedUrl,
      'notes': notes,
    });
    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return json['success'] == true;
    }
    return false;
  }

  /// Mengunggah entri baru beserta foto kamera langsung dari smartphone
  static Future<bool> uploadEntryWithPhoto({
    required Map<String, String> fields,
    File? imageFile,
  }) async {
    final uri = Uri.parse('$baseUrl/api.php?endpoint=entry_create&user=$activeUserKey');
    final request = http.MultipartRequest('POST', uri);

    request.fields.addAll(fields);

    if (imageFile != null) {
      request.files.add(
        await http.MultipartFile.fromPath('attachment', imageFile.path),
      );
    }

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode == 201 || response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return json['success'] == true;
    } else {
      final json = jsonDecode(response.body);
      throw Exception(json['errors'] ?? 'Gagal menyimpan data capaian');
    }
  }
}
