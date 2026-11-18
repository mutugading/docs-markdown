body {font-family: Arial, Helvetica, sans-serif;}
* {box-sizing: border-box;}

/* Button used to open the contact form - fixed at the bottom of the page */
.open-button {
  background-color: #555;
  color: white;
  padding: 6px 10px;
  border: none;
  cursor: pointer;
  opacity: 0.8;
  position: fixed;
  bottom: 23px;
  right: 28px;
  width: 100px;
}

/* The popup form - hidden by default */
.form-popup {
  display: none;
  position: fixed;
  bottom: 0;
  right: 15px;
  border: 3px solid #f1f1f1;
  z-index: 9;
}

/* Add styles to the form container */
.form-container {
  max-width: 300px;
  padding: 10px;
  background-color: white;
}

/* Full-width input fields */
.form-container input[type=text], .form-container input[type=password] {
  width: 100%;
  padding: 15px;
  margin: 5px 0 22px 0;
  border: none;
  background: #f1f1f1;
}

/* When the inputs get focus, do something */
.form-container input[type=text]:focus, .form-container input[type=password]:focus {
  background-color: #ddd;
  outline: none;
}

/* Set a style for the submit/login button */
.form-container .btn {
  background-color: #04AA6D;
  color: white;
  padding: 16px 20px;
  border: none;
  cursor: pointer;
  width: 100%;
  margin-bottom:10px;
  opacity: 0.8;
}

/* Add a red background color to the cancel button */
.form-container .cancel {
  background-color: red;
}

/* Add some hover effects to buttons */
.form-container .btn:hover, .open-button:hover {
  opacity: 1;
}

/* calculator */
  body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      text-align: center;
      margin: 15px;
      background-color: #f0f0f0;
  }
  #calculator {
      width: min-content;
      margin: auto;
      margin-top: 15px;
      margin-right: 15px;
      background-color: #fff;
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      grid-gap: 5px;
  }
  input[type="text"] {
      grid-column: span 5;
      margin-top: 0;
      margin-bottom: 0;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 5px;
      outline: none;
      overflow: scroll;
  }
  #result {
      grid-column: span 5;
      margin-top: 10px;
      margin-bottom: 0;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 5px;
      background-color: #f0f0f0;
  }
  input[type="button"] {
      width: 35px;
      padding: 2px;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 5px;
      background-color: #f0f0f0;
      cursor: pointer;
  }
  input[type="button"]:hover {
      background-color: #ddd;
  }
  input[type="button"].operator {
      background-color: #ff9800;
      color: #fff;
      border: 1px solid #ff9800;
  }
  input[type="button"].operator:hover {
      background-color: #e08e00;
  }
  input[type="button"].equals {
      /* grid-column: span 2; */
      background-color: #4caf50;
      color: #fff;
      border: 1px solid #4caf50;
  }
  input[type="button"].equals:hover {
      background-color: #45a049;
  }
  input[type="button"].delete {
      background-color: #f44336;
      color: #fff;
      border: 1px solid #f44336;
  }
  input[type="button"].delete:hover {
      background-color: #d32f2f;
  }
/*calcualtor*/
