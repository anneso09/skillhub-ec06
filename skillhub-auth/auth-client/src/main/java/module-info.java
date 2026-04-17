module com.example.authclient {
    requires javafx.controls;
    requires javafx.fxml;
    requires java.net.http;

    opens com.example.authclient to javafx.fxml;
    exports com.example.authclient;
}