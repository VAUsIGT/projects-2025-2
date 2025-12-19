#include<iostream>
#include <winsock2.h>
#include<ws2tcpip.h>

#pragma comment(lib, "ws2_32.lib");
using namespace std;


int main() {
    WSADATA data;
    WORD version = MAKEWORD(2, 2);

    int wsOk = WSAStartup(version, &data);
    if (wsOk != 0) {
        cout << "Cant start Winsock!" << wsOk;
        return 1;
    }

    SOCKET in = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
    if (in == INVALID_SOCKET) {
        cout << "Can't create socket! " << WSAGetLastError() << endl;
        WSACleanup();
        return 1;
    }


    sockaddr_in serverHint;
    serverHint.sin_addr.S_un.S_addr = ADDR_ANY;
    serverHint.sin_family = AF_INET;

    serverHint.sin_port = htons(54000);
    if (bind(in, (sockaddr*)&serverHint, sizeof(serverHint)) == SOCKET_ERROR) {
        cout << "Cant bind socket!" << WSAGetLastError() << endl;
        return 1;
    }

    sockaddr_in client;
    if (listen(in, SOMAXCONN) == SOCKET_ERROR) {
        cout << "Can't listen on socket! " << WSAGetLastError() << endl;
        closesocket(in);
        WSACleanup();
        return 1;
    }
    cout << "Server is listening on port 54000..." << endl;

    int clientLength = sizeof(client);
    char buf[1024];

    while (true) {
        SOCKET clientSocket = accept(in, (sockaddr*)&client, &clientLength);
        if (clientSocket == INVALID_SOCKET) {
            cout << "Accept failed! " << WSAGetLastError() << endl;
            continue;
        }

        char clientIp[256];
        ZeroMemory(clientIp, 256);
        inet_ntop(AF_INET, &client.sin_addr, clientIp, 256);
        cout << "Client connected from: " << clientIp << endl;

        char buf[1024];
        while (true) {
            ZeroMemory(buf, 1024);

            int bytesIn = recv(clientSocket, buf, 1024, 0);
            if (bytesIn == SOCKET_ERROR) {
                cout << "Error receiving from client " << WSAGetLastError() << endl;
                break;
            }

            if (bytesIn == 0) {
                cout << "Client disconnected" << endl;
                break;
            }

            cout << "Msg received: " << buf << endl;

        }

        closesocket(clientSocket);
    }

    closesocket(in);
    WSACleanup();
    return 0;
}