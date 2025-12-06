<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التقرير السنوي لإحصائيات الشكاوى - {{ $year }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
        }
        .page-title {
            text-align: center;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .year-text {
            text-align: center;
            margin-bottom: 25px;
            color: #555;
        }
        .card {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 18px;
        }
        .card-header {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #222;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #999;
            padding: 6px;
            text-align: center;
        }
        thead {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .total-row {
            background-color: #fafafa;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="page-title">التقرير السنوي لإحصائيات الشكاوى</div>
<div class="year-text">لسنة {{ $year }}</div>

@forelse($items as $row)
    <div class="card">
        <div class="card-header">
            {{ $row['agency_name'] }}
        </div>

        <table>
            <thead>
            <tr>
                <th>معلقة</th>
                <th>قيد المعالجة</th>
                <th>تحتاج معلومات إضافية</th>
                <th>مرفوضة</th>
                <th>تم إنجازها</th>
                <th>المجموع</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $row['pending'] }}</td>
                <td>{{ $row['under_processing'] }}</td>
                <td>{{ $row['needs_additional_info'] }}</td>
                <td>{{ $row['rejected'] }}</td>
                <td>{{ $row['resolved'] }}</td>
                <td>{{ $row['total'] }}</td>
            </tr>
            </tbody>
        </table>
    </div>
@empty
    <p style="text-align:center; margin-top:40px;">لا توجد بيانات شكاوى لهذه السنة.</p>
@endforelse

</body>
</html>
