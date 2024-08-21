<html lang='en-GB '>
  <head ><title >OurBank </title ></head >
<body >
        <form method = "post">
<?php
    // Connection to database:
    $host = "studdb.csc.liv.ac.uk";
    $user = "sgaterak"; 
    $passwd = "gun356fg"; 
    $db = "sgaterak"; 
    $charset = "utf8mb4";
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    
    $opt = array(
    PDO:: ATTR_ERRMODE => PDO:: ERRMODE_EXCEPTION ,
    PDO:: ATTR_DEFAULT_FETCH_MODE => PDO:: FETCH_ASSOC ,
    PDO:: ATTR_EMULATE_PREPARES => false
    );
    try {
      $pdo = new PDO($dsn ,$user ,$passwd ,$opt);
      
      $sql = "SELECT `Account number`, `Account holder` FROM `bank`";
      $stmt = $pdo->prepare($sql);
      $stmt->execute();
      $payers = $stmt->fetchAll();
      
      } catch (PDOException $e) {
      echo 'Connection failed: ',$e ->getMessage ();
    }
    
    
    
    // These are dropdown boxes that allow user to select payer and payee account numbers:
      ?>  
<div>  
    <label >Select payers bank account:
      <select name='payer'required>
      <option value="">--- Select ---</option>
      <?php foreach($payers as $payer): ?>
        <option value="<?= $payer['Account number']; ?>"><?= $payer['Account number']; ?></option>  
    <?php endforeach; ?>,
      </select >
    </label >
</div>
       
<div>  
    <label >Select payees bank account:
      <select name='payee'required>
      <option value="">--- Select ---</option>
        <?php foreach($payers as $payee): ?>
        <option value="<?= $payee['Account number']; ?>"><?= $payee['Account number']; ?></option>  
    <?php endforeach; ?>,
      </select >
    </label >
</div>


    <!-- amount and reference_text input boxes: -->
    <div class="field">  
    <label for="amount" >Enter amount you want to transfer:
      <input type='text' name='amount' id ='amount'>
    </label >
    </div>
    
    
    <div class="field">  
    <label for="reference">Enter the reference text:
      <input type='text'name='reference' id ='reference'>
    </label >
    </div>
    
    
    <?php
    
    $balance = array();
    
    // maps and stores balance, overdraft from fetch function to $balance array:
    //(user's initial balance maped to user's account number, and overdraft to  user's account number + 1)
    function storeBalance($id ,$b, $over) {
      global $balance;
      $balance [$id] = $b;
      $balance [$id+1] = $over;
      }

    // stores input from drowpdown boxes and entered values
    $flag = "Transfer is unsuccessfull \n";
    if(isset($_POST["submit"])) {
      $payee = $_POST['payee'];
      $payer = $_POST['payer'];
      $txt_amount = $_POST['amount'];
      $amount = doubleval($txt_amount);
      $reference = $_POST['reference'];
      
      // validation to ensure taht user input's allowed values
      if ($amount > 10000 or $amount < 0){
          echo $flag;
          echo 'Amount should be between 0 and 10000';}
      elseif ($payer == $payee){
          echo $flag;
          echo ' Account numbers must be different';}
      elseif (strlen($reference) > 20){
          echo $flag;
          echo 'Reference must be at most 20 characters long';}
      else{
        try{
        // selects account num, balance, and overdraft from table where account num is similar to one selected in dropdown boxes:
        $pdo->beginTransaction();
        $sql = "SELECT `Account number`, `Initial balance`, `Overdraft` FROM `bank` WHERE `Account number` = ? or `Account number` = ? for update";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($payer, $payee));
        $stmt ->fetchAll(PDO::FETCH_FUNC ,'storeBalance');
        $overall = $balance[$payer] + $balance[$payer+1];
        if ($overall < $amount){ // another validation
          echo $flag;
          echo 'Insuffisient funds';
            }else{
            // transfer for user that has enough money in inital balance:
              if ($balance[$payer] >= $amount){
                   $sql = "UPDATE `bank` SET `Initial balance` = `Initial balance` + ? WHERE `Account number` = ?";
                   $stmt = $pdo->prepare($sql);
                   $stmt->execute(array(-$amount, $payer));
                   $stmt->execute(array($amount, $payee));
                   $pdo ->commit();
                   echo "Amount has been transfered succesfully!";
            }else{
            // transfer for user that doesn't have enough inital balance, but has overdraft for payment option:
                   $sql = "UPDATE `bank` SET `Initial balance` = `Initial balance` + ?, `Overdraft` = `Overdraft` + ? WHERE `Account number` = ?";
                   $stmt = $pdo->prepare($sql);
                   $ov = $amount - $balance[$payer];
                   
                   $stmt->execute(array(-$balance[$payer], -$ov, $payer));
                   $stmt->execute(array($amount, 0, $payee));
                   $pdo ->commit();
                   echo "Amount has been transfered succesfully!";
                }
            }
        } catch (PDOException $e){
          echo 'Connection failed: ',$e ->getMessage ();
          }
      }
      }  
    ?>
    
    
    <input type="submit" name='submit' value="Submit">
    
</form>
</body>
</html>

    
    
