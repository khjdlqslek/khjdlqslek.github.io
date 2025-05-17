<?php
date_default_timezone_set("Asia/Seoul");

$items = [
    ["입장권", 7000, 10000],
    ["BIG3", 12000, 16000],
    ["자유이용권", 21000, 28000],
    ["연간이용권", 70000, 90000]
];

$total = 0;
$output = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['customer_name'];
    $child_quantities = $_POST['child'];
    $adult_quantities = $_POST['adult'];

    $child_tickets = [];
    $adult_tickets = [];

    $lines = [];
    for ($i = 0; $i < count($items); $i++) {
        $c_qty = (int)$child_quantities[$i];
        $a_qty = (int)$adult_quantities[$i];
        $child_tickets[] = $c_qty;
        $adult_tickets[] = $a_qty;

        $total += $c_qty * $items[$i][1];
        $total += $a_qty * $items[$i][2];

        // 조건부 출력 추가
        if ($c_qty > 0 && $items[$i][0] == "입장권") {
            $lines[] = "어린이 입장권: {$c_qty}";
        }
        if ($a_qty > 0 && $items[$i][0] == "BIG3") {
            $lines[] = "어른 BIG3: {$a_qty}";
        }
    }

    // DB 저장
    $conn = new mysqli('localhost', 'root', '', 'tickets_db');
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("INSERT INTO ticket_orders (customer_name, child_tickets, adult_tickets, total_amount) VALUES (?, ?, ?, ?)");

        // ✅ 함수 결과를 변수에 저장 (Strict Standards 해결)
        $child_json = json_encode($child_tickets);
        $adult_json = json_encode($adult_tickets);

        $stmt->bind_param("sssi", $name, $child_json, $adult_json, $total);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }

    $now = date("Y년 m월 d일 H:i:s");
    $output = "<p>{$now} ({$name}) 고객님 감사합니다.<br>";
    foreach ($lines as $line) {
        $output .= $line . "<br>";
    }
    $output .= "합계: <strong>" . number_format($total) . "원</strong> 입니다.</p>";
}
?>

<form method="post">
    <label>고객성명: <input type="text" name="customer_name" required></label>
    <table border="1">
        <tr><th>No</th><th>구분</th><th>어린이</th><th>어른</th><th>비고</th></tr>
        <?php foreach ($items as $i => $item): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= $item[0] ?></td>
            <td>
                <select name="child[]">
                    <?php for ($j = 0; $j <= 5; $j++) echo "<option value='$j'>$j</option>"; ?>
                </select>
            </td>
            <td>
                <select name="adult[]">
                    <?php for ($j = 0; $j <= 5; $j++) echo "<option value='$j'>$j</option>"; ?>
                </select>
            </td>
            <td>
                <?= ($item[0] == '입장권' ? '입장' : '입장+놀이' . ($item[0] == 'BIG3' ? '3종' : '자유')) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <button type="submit">합계</button>
</form>

<?= $output ?>
