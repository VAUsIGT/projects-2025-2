#include<iostream>
#include<ws2tcpip.h>

#pragma comment (lib, "ws2_32.lib")

using namespace std;

int main() {
    WSADATA data;

    int wsOk = WSAStartup(MAKEWORD(2, 2), &data);
    if (wsOk != 0) {
        cout << "Cant start Winsock! " << wsOk;
        return 1;
    }

    sockaddr_in server;
    server.sin_family = AF_INET;
    server.sin_port = htons(54000);
    inet_pton(AF_INET, "127.0.0.1", &server.sin_addr);

    SOCKET out = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
    char msg[1024]; // Буфер для ввода сообщения

    if (connect(out, (sockaddr*)&server, sizeof(server)) == SOCKET_ERROR) {
        cout << "Can't connect to server! " << WSAGetLastError() << endl;
        closesocket(out);
        WSACleanup();
        return 1;
    }
    cout << "Connected to server. Type messages to send (type 'exit' to quit):" << endl;
    while (true) {
        ZeroMemory(msg, 1024);
        cout << "> ";
        cin.getline(msg, 1024);

        if (strcmp(msg, "exit") == 0) {
            cout << "Exiting..." << endl;
            break;
        }

        int sendResult = send(out, msg, strlen(msg) + 1, 0);
        if (sendResult == SOCKET_ERROR) {
            cout << "Send failed! " << WSAGetLastError() << endl;
            break;
        }


    }
    closesocket(out);
    WSACleanup();

    return 0;
}