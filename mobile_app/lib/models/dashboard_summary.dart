// lib/models/dashboard_summary.dart

class UnitProgress {
  final String unitCode;
  final String unitName;
  final int totalEntries;
  final int signatureCount;
  final bool isSignatureFulfilled;
  final int siapDiolah;
  final int belumDiolahKom;
  final int sedangDiolah;
  final int sudahTerbit;

  UnitProgress({
    required this.unitCode,
    required this.unitName,
    required this.totalEntries,
    required this.signatureCount,
    required this.isSignatureFulfilled,
    required this.siapDiolah,
    required this.belumDiolahKom,
    required this.sedangDiolah,
    required this.sudahTerbit,
  });

  factory UnitProgress.fromJson(Map<String, dynamic> json) {
    return UnitProgress(
      unitCode: json['unit_code'] ?? '',
      unitName: json['unit_name'] ?? '',
      totalEntries: json['total_entries'] ?? 0,
      signatureCount: json['signature_count'] ?? 0,
      isSignatureFulfilled: json['is_signature_fulfilled'] ?? false,
      siapDiolah: json['siap_diolah'] ?? 0,
      belumDiolahKom: json['belum_diolah_kom'] ?? 0,
      sedangDiolah: json['sedang_diolah'] ?? 0,
      sudahTerbit: json['sudah_terbit'] ?? 0,
    );
  }
}

class DashboardData {
  final String periodId;
  final String periodName;
  final int totalEntries;
  final int signatureFulfilledCount;
  final int totalUnits;
  final int pendingCommCount;
  final List<UnitProgress> progressList;

  DashboardData({
    required this.periodId,
    required this.periodName,
    required this.totalEntries,
    required this.signatureFulfilledCount,
    required this.totalUnits,
    required this.pendingCommCount,
    required this.progressList,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    final summary = json['summary'] as Map<String, dynamic>? ?? {};
    final list = (json['progress_meter'] as List<dynamic>? ?? [])
        .map((e) => UnitProgress.fromJson(e as Map<String, dynamic>))
        .toList();

    return DashboardData(
      periodId: json['period_id'] ?? '',
      periodName: json['period_name'] ?? '',
      totalEntries: summary['total_entries'] ?? 0,
      signatureFulfilledCount: summary['signature_fulfilled'] ?? 0,
      totalUnits: summary['total_units'] ?? 4,
      pendingCommCount: summary['pending_comm'] ?? 0,
      progressList: list,
    );
  }
}
