<?php
require_once('../../../private/sysNotification.php');
require_once('../../../private/config.php');
require_once('../../../private/userbase.php');
require_once('../../../private/functions.php');

forceHTTPS(); // Force https connection
session_start(); // Start Session
checkClientStatus(); // Check if the client is signed in

/* Constants */
const AMOUNT_OF_TRANSACTIONS = 5; // Number of recent transactions to show

/* SESSION Variables */
$userId = $_SESSION['uid'];
$userId = 1;

/* Temp Variables */
$user = 'User'; // Taken from DB, user account name
$lastVisit = date("F j, Y, g:i a"); // Last time of login

/* Main Variables */
$totalBalance = 0.00;
$accounts = array(); // Array that possess the names of the accounts under the current client

/* Get Database Connection */
$db = getUpdateConnection();

/* Check connection */
if ($db === null){
	header("Location: ");
	die();
}
?>

<!DOCTYPE html>
<html lang="en-US">
	<head>
	<title>Home</title>
	<!-- Stylesheet -->
	<link rel="stylesheet" href="../css/stylesheet.css">
	<!-- Favicon -->
	<link rel="icon" href="../img/logo.ico">
	<!-- Google Font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <!-- Google Font -->
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <!-- Google Font -->
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100&display=swap" rel="stylesheet">
        <!-- Svg Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
        <!-- Different screen size scaling compatability -->
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body>
		<nav class="menubar">
			<ul class="menugroup">
				<li class="menulogo"><a href="home">Goldman Stacks</a></li>
                <li class="menutoggle"><a href="#"><i class="fas fa-bars"></i></a></li>
				<li class="menuitem"><a href="home">Home</a></li>
				<li class="menuitem"><a href="account/transfer">Transfer</a></li>
				<li class="menuitem"><a href="account/payments">Payments</a></li>
				<li class="menuitem"><a href="account/open">Open New Account</a></li>
				<li class="menuitem"><a href="account/statement">Statement</a></li>
			</ul>
			<ul class="menugroup">
				<li class="menuitem"><a href="user/options">Options</a></li>
				<li class="menuitem"><a href="../requests/signout">Sign Out</a></li>
			</ul>
		</nav>
		<?php notification(); ?>
		<div class="container flex-center">
		    <div class="list main">
		        <h2 id="title">Welcome, <?php echo $user ?></h2>
		        <div class="split">
		            <label class="info">Available Accounts</label>
		            <a href="account/open" class="expand-button transform-button extend-left round shadow">
		                <div class="split">
		                    <div class="animate-left">
            		            <div class="toggle-button">
            		                <p class="expanded-info">Add Account</p>
            		            </div>
        		            </div>
		                    <p class="condensed-info animate-rotate90"><b>+</b></p>
		                </div>
		            </a>
		        </div>
		        <?php			
				/* Statement to get client account information */
				$accountsStatement = $db->prepare("SELECT accountNum, accountType, balance, nickName FROM accountDirectory WHERE clientID=?");
				$accountsStatement->bind_param("i", $userId);
				$accountsStatement->execute();
				
				/* Obtain reults */
				$accountsResult = $accountsStatement->get_result();
				$accountsRows = $accountsResult->fetch_all(MYSQLI_ASSOC);
					
				foreach ($accountsRows as $account) {
					$accounts[] = $account['nickName'];
					$accNumber = substr($account['accountNum'], -4); // Temp Variable (to be deleted)
					
					/* Determine if account type is credit or not */
					if ($account['accountType'] == 'credit') {
					$totalBalance -= $account['balance'];
					$account_message = "Credit Used";
					} else {
					$totalBalance += $account['balance'];
					$account_message = "Available";
					}
					
						/* Create button for each account */
					echo "<a href=\"account/details.php?acc=".htmlspecialchars($account['nickName'])."\" class=\"big-color-button transform-button split round shadow\">
						<div class=\"list\">
							<p class=\"focused-info\">".htmlspecialchars($account['nickName'])."</p>
							<p>".ucfirst($account['accountType'])." account (*".htmlspecialchars(substr($account['accountNum'], -4)).")</p>
						</div>
						<div class=\"split animate-left\">
							<div class=\"list text-right\">
							<p>".$account_message."</p>
								<p class=\"focused-info\">\$".$account['balance']."</p>
							</div>
							<div class=\"toggle-button\">
							<i class=\"fas fa-chevron-right\"></i>
							</div>
						</div>
					</a>";
				}
				
				$accountsResult->free();
				$accountsStatement->close();
				?>
		    </div>
		    <div class="list sub">
		        <div class="container round shadow">
    		        <div class="item-banner top-round">
    		            <h2 class="big text-center">Total Balance: $<?php echo $totalBalance ?></h2>
    		        </div>
    		        <div class="item-content bottom-round">
    		            <p class="info text-center">Last Sign In: <?php echo $lastVisit ?></p>
    		            <hr>
                        <a href="account/funds" class="highlight-button transform-button split round">
                            <div class="list">
                                <p><i class="fas fa-plus icon"></i> Deposit Funds</p>
                            </div>
                            <div class="animate-left">
                	            <div class="toggle-button">
                	                <i class="fas fa-chevron-right"></i>
                	            </div>
                            </div>
                        </a>
                        <hr>
                        <a href="account/funds.php?v=withdraw" class="highlight-button transform-button split round">
                            <div class="list">
                                <p><i class="fas fa-minus icon"></i> Withdraw Funds</p>
                            </div>
                            <div class="animate-left">
                	            <div class="toggle-button">
                	                <i class="fas fa-chevron-right"></i>
                	            </div>
                            </div>
                        </a>
    		        </div>
    		    </div>
		        <div class="container round shadow">
    		        <div class="item-banner top-round">
    		            <label class="banner-text"> Recent Activity</label>
    		        </div>
    		        <div class="item-content bottom-round">
    		            <?php    		            
						/* Statement to obtain transaction information */
						$transactionStatement = $db->prepare("SELECT transactionTime, transactionAmount, type FROM transactions WHERE clientID=? LIMIT ".AMOUNT_OF_TRANSACTIONS);
						$transactionStatement->bind_param("i", $userId);
						$transactionStatement->execute();
						
						/* Obtain results */
						$transactionResult = $transactionStatement->get_result();
						$transactionRows = $transactionResult->fetch_all(MYSQLI_ASSOC);
												
						foreach ($transactionRows as $transaction) {							
							echo "<a href=\"account/details.php?acc=".$recentAccount."\" class=\"highlight-button transform-button split round\">
									<div class=\"list-padded\">
										<h3 class=\"bold\">Transaction</h3>
										<p>".$transaction['transactionTime']."<p>
									</div>
									<div class=\"split animate-left\">
										<div class=\"list-padded text-right\">
											<h3>".convertToCurrency($transaction['transactionAmount'])."</h3>
											<p>".ucfirst($transaction['type'])."</p>
										</div>
										<div class=\"toggle-button\">
											<i class=\"fas fa-chevron-right\"></i>
										</div>
									</div>
								</a>";
							
							if ($transaction != end($transactionRows)){
								echo "<hr>";
							}
						}
				
						$transactionResult->free();
						$transactionStatement->close();
    		            ?>
    		        </div>
    		    </div>
		        <div class="container round shadow">
    		        <div class="item-banner top-round">
    		            <label class="banner-text">Quick Payments</label>
    		        </div>
    		        <form id="payments" class="item-content bottom-round">
        		        <label class="info" for="PayTo">Pay To</label>
    		            <div class="form-item">
        		            <input id="PayTo" class="input-field" type="text" required>
    		            </div>
        		        <label class="info" for="PayFrom">Pay From</label>
    		            <div class="form-item">
        		            <select id="PayFrom" class="input-field">
                                <?php
								foreach ($accounts as $account) {
									echo "<option>$account</option>";
								}
                                ?>
        		            </select>
        		        </div>
    		            <label class="info" for="Date">Date</label>
    		            <div class="form-item">
        		            <input id="Date" class="input-field" type="date" placeholder="yyyy-mm-dd">
    		            </div>
    		            <label class="info" for="Amount">Amount</label>
    		            <div class="form-item">
        		            <input id="Amount" class="input-field" type="number" min="0" max="<?php echo $totalBalance ?>">
    		            </div>
    		            <div class="form-item">
                            <button form="payments" class="standard-button transform-button flex-center round">
                                <div class="split">
                                    <p class="animate-left">Schedule Payment<p>
                   		            <div class="toggle-button">
                    		            <i class="fas fa-chevron-right"></i>
                    		        </div>
                                </div>
                            </button>
    		            </div>
    		        </form>
    		    </div>
		    </div>
		</div>
	</body>
	<script type="text/javascript" src="../js/navigation.js"></script>
	<script type="text/javascript" src="../js/post.js"></script>
</html>
<?php
$db->close();