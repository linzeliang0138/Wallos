<?php
    require_once '../../includes/connect_endpoint.php';
    
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        if ($_SERVER["REQUEST_METHOD"] === "PUT") {
            $subscriptionId = $_GET["id"];

            // 获取订阅规则列表
            $cycles = array();
            $query = "SELECT * FROM cycles";
            $result = $db->query($query);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $cycleId = $row['id'];
                $cycles[$cycleId] = $row;
            }

            // 获取待更新的数据
            $query = "SELECT id, next_payment, frequency, cycle FROM subscriptions WHERE id = :subscriptionId";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':subscriptionId', $subscriptionId);
            $result = $stmt->execute();

            // 更新
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $subscriptionId = $row['id'];
                $nextPaymentDate = new DateTime($row['next_payment']);
                $frequency = $row['frequency'];
                $cycle = $cycles[$row['cycle']]['name'];
    
                // Calculate the interval to add based on the cycle
                $intervalSpec = "P";
                if ($cycle == 'Daily') {
                    $intervalSpec .= "{$frequency}D";
                } elseif ($cycle === 'Weekly') {
                    $intervalSpec .= "{$frequency}W";
                } elseif ($cycle === 'Monthly') {
                    $intervalSpec .= "{$frequency}M";
                } elseif ($cycle === 'Yearly') {
                    $intervalSpec .= "{$frequency}Y";
                }
    
                $interval = new DateInterval($intervalSpec);
    
                // 增加一个周期的下个付款日
                $nextPaymentDate->add($interval);
    
                // Update the subscription's next_payment date
                $updateQuery = "UPDATE subscriptions SET next_payment = :nextPaymentDate WHERE id = :subscriptionId";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindValue(':nextPaymentDate', $nextPaymentDate->format('Y-m-d'));
                $updateStmt->bindValue(':subscriptionId', $subscriptionId);
    
                if ($updateStmt->execute()) {
                    $response = [
                        "success" => true,
                        "message" => translate('success', $i18n)
                    ];
                    echo json_encode($response);
                } else {
                    die(json_encode([
                        "success" => false,
                        "message" => translate("error", $i18n)
                    ]));
                }
            }
        } else {
            http_response_code(405);
            echo json_encode(array("message" => translate('invalid_request_method', $i18n)));
        }
    }
    $db->close();
?>