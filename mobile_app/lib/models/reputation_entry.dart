// lib/models/reputation_entry.dart

class ReputationEntry {
  final int id;
  final String unitCode;
  final String unitName;
  final String periodId;
  final String periodName;
  final String tierType;
  final String title;
  final String shortDescription;
  final String screeningStatus;
  final String commStatus;
  final String? commPublishedUrl;
  final String namePublicationConsent;
  final String? attachmentName;
  final String? attachmentPath;
  final int? attachmentSizeBytes;
  final String? externalUrl;
  final String? notes;
  final String createdAt;
  final Map<String, dynamic>? details;

  ReputationEntry({
    required this.id,
    required this.unitCode,
    required this.unitName,
    required this.periodId,
    required this.periodName,
    required this.tierType,
    required this.title,
    required this.shortDescription,
    required this.screeningStatus,
    required this.commStatus,
    this.commPublishedUrl,
    required this.namePublicationConsent,
    this.attachmentName,
    this.attachmentPath,
    this.attachmentSizeBytes,
    this.externalUrl,
    this.notes,
    required this.createdAt,
    this.details,
  });

  bool get isSignature => tierType == 'SIGNATURE';
  bool get isSiapDiolah => screeningStatus == 'SIAP_DIOLAH';
  bool get isRestricted => namePublicationConsent == 'TIDAK';

  factory ReputationEntry.fromJson(Map<String, dynamic> json) {
    return ReputationEntry(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      unitCode: json['unit_code'] ?? '',
      unitName: json['unit_name'] ?? json['unit_code'] ?? '',
      periodId: json['period_id'] ?? '',
      periodName: json['period_name'] ?? json['period_id'] ?? '',
      tierType: json['tier_type'] ?? 'REGULER',
      title: json['title'] ?? '',
      shortDescription: json['short_description'] ?? '',
      screeningStatus: json['screening_status'] ?? 'DRAFT',
      commStatus: json['comm_status'] ?? 'BELUM_DIOLAH',
      commPublishedUrl: json['comm_published_url'],
      namePublicationConsent: json['name_publication_consent'] ?? 'YA',
      attachmentName: json['attachment_name'],
      attachmentPath: json['attachment_path'],
      attachmentSizeBytes: json['attachment_size_bytes'] != null
          ? int.tryParse(json['attachment_size_bytes'].toString())
          : null,
      externalUrl: json['external_url'],
      notes: json['notes'],
      createdAt: json['created_at'] ?? '',
      details: json['details'] is Map<String, dynamic> ? json['details'] : null,
    );
  }
}
