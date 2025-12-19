#include<iostream>
#include<thread>
#include<mutex>
#include<string>
#include<chrono>

void Func(std::string name);

// std::mutex m;

int main() {
    std::thread thread1(Func, "t1");
    std::thread thread2(Func, "t2");
    std::thread thread3(Func, "t3");

    thread1.join();
    thread2.join();
    thread3.join();

    system("pause");

    return 0;
}

void Func(std::string name) {
    long double i = 0;
    auto start = std::chrono::high_resolution_clock::now();
    while (true) {
        auto now = std::chrono::high_resolution_clock::now();
        auto elapsed = std::chrono::duration_cast<std::chrono::seconds>(now - start);

        if (elapsed.count() >= 1) {
            // m.lock();
            std::cout << name << ": " << i << std::endl;
            // m.unlock(); 
            return;
        }

        i += 1e-9;
    }
}