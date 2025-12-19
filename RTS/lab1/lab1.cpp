#include<iostream>
#include<time.h>
#include <thread>
#include <mutex>
#include <string>

// #1

//int main()
//	{
//	std::string s = "01234";
//	for (unsigned int i = s.size() - 1; i >= 0; i--)
//	{
//		std::cout << s[i] << std::endl;
//	}
//	return 0;
//}

// #2
//
//int main() {
//    // Принудительно отключаем некоторые оптимизации для факториала
//    volatile int prevent_optimization = 0;  // volatile запрещает оптимизацию
//    long long total_sum = 0;  // Накопим сумму, чтобы использовать результат
//
//    clock_t start = clock();
//
//    for (int i = 0; i < 10'000'000; i++) {
//        int fact = 1;
//        for (int j = 1; j <= 10; j++) {
//            fact *= j;
//        }
//        // Используем результат, чтобы компилятор не удалил вычисления
//        total_sum += fact;
//        prevent_optimization = fact;  // Запрещаем оптимизацию
//    }
//
//    clock_t end = clock();
//
//    double seconds = (double)(end - start) / CLOCKS_PER_SEC;
//
//    std::cout << "Time of work: " << seconds << " seconds" << std::endl;
//
//    // Задержка для просмотра результата
//    system("pause");
//    return 0;
//}

// #3

void Func(std::string name);

int main() {
    
    clock_t start = clock();
    std::thread thread1(Func, "t1");
    std::thread thread2(Func, "t2");

    thread1.join();
    thread2.join();

    Func("1");
    Func("2");

    clock_t end = clock();
    double seconds = (double)(end - start) / CLOCKS_PER_SEC;
    std::cout << "Time of work: " << seconds << std::endl;

    system("pause");

    return 0;
}
void Func(std::string name) {
    clock_t start = clock();
    for (int i = 0; i <= 10'000'000; i++) {
        int fact = 1;
        for (int j = 1; j <= 10; j++) {
            fact *= j;
        }
    }
}