class RestaurantBinding {
  const RestaurantBinding({
    required this.endpoint,
    required this.name,
    required this.code,
    required this.mode,
    required this.deviceName,
  });
  final String endpoint, name, code, mode, deviceName;
}

class WaiterUser {
  const WaiterUser({
    required this.id,
    required this.name,
    required this.username,
  });
  final String id, name, username;
  factory WaiterUser.fromJson(Map<String, dynamic> json) => WaiterUser(
    id: json['id'].toString(),
    name: json['name'].toString(),
    username: json['username']?.toString() ?? '',
  );
}

class WaiterTable {
  const WaiterTable({
    required this.sessionId,
    required this.tableId,
    required this.areaName,
    required this.tableLabel,
    required this.total,
    required this.openedAt,
    required this.ordersCount,
    required this.billRequested,
    required this.customerRequests,
  });
  final String sessionId, tableId, areaName, tableLabel, total;
  final DateTime openedAt;
  final int ordersCount, customerRequests;
  final bool billRequested;
  factory WaiterTable.fromJson(Map<String, dynamic> json) => WaiterTable(
    sessionId: json['sessionId'].toString(),
    tableId: json['tableId'].toString(),
    areaName: json['areaName'].toString(),
    tableLabel: json['tableLabel'].toString(),
    total: json['currentTotal'].toString(),
    openedAt: DateTime.parse(json['openedAt'].toString()),
    ordersCount: json['ordersCount'] as int? ?? 0,
    billRequested: json['billRequested'] == true,
    customerRequests: json['customerRequestCount'] as int? ?? 0,
  );
}
