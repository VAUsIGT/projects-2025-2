#include <iostream>
#include <thread>
#include <chrono>
#include <atomic>
#include <vector>

//собственный мьютекс на основе атомарных переменных
class MyMutex {
private:
    std::atomic<bool> locked{ false };  //флаг блокировки

public:
    //метод блокировки (аналог lock)
    void lock() {
        //compare_exchange_weak для атомарной проверки и установки флага
        bool expected = false;
        //ждем, пока не удастся установить флаг в true
        while (!locked.compare_exchange_weak(expected, true,
            std::memory_order_acquire,
            std::memory_order_relaxed)) {
            expected = false;  //сбрасываем expected для следующей попытки
            //добавляем небольшую паузу, чтобы не нагружать процессор
            std::this_thread::yield();
        }
    }

    //метод разблокировки (аналог unlock)
    void unlock() {
        //атомарно сбрасываем флаг блокировки
        locked.store(false, std::memory_order_release);
    }

    //метод попытки блокировки (аналог try_lock)
    bool try_lock() {
        bool expected = false;
        return locked.compare_exchange_weak(expected, true,
            std::memory_order_acquire,
            std::memory_order_relaxed);
    }
};

//RAII-обертка для нашего мьютекса (аналог lock_guard)
class MyLockGuard {
private:
    MyMutex& mutex;

public:
    explicit MyLockGuard(MyMutex& mtx) : mutex(mtx) {
        mutex.lock();
    }

    ~MyLockGuard() {
        mutex.unlock();
    }

    //запрет на копирование и присваивание
    MyLockGuard(const MyLockGuard&) = delete;
    MyLockGuard& operator=(const MyLockGuard&) = delete;
};

const int DESTROY_THRESHOLD = 10000;
const int INITIAL_DISH = 3000;
const int DAY_SECONDS = 5; // 1 день = 1 секунда

//наш мьютекс вместо std::mutex
MyMutex mtx;
std::atomic<bool> chef_fired(false);
std::atomic<bool> chef_left(false);
std::atomic<bool> chef_no_salary(false);
std::atomic<bool> chef_working(true);
std::atomic<bool> simulation_running(true);

class Eater {
public:
    std::atomic<bool> alive;
    std::atomic<bool> can_eat;
    int gluttony;
    int& dish;
    int eaten;
    std::string name;

    Eater(int g, int& d, std::string n)
        : gluttony(g), dish(d), name(n), eaten(0), alive(true), can_eat(true) {
    }
};

void cook(int efficiency, std::vector<Eater*>& eaters, std::vector<int>& dishes) {
    auto start = std::chrono::steady_clock::now();

    while (simulation_running && !chef_fired && !chef_left && !chef_no_salary) {
        //проверка на 5 дней
        auto now = std::chrono::steady_clock::now();
        auto elapsed = std::chrono::duration_cast<std::chrono::seconds>(now - start);

        if (elapsed.count() >= DAY_SECONDS) {
            chef_left = true;
            simulation_running = false;
            std::cout << "\nКук уволился по истечению 5 дней." << std::endl;
            return;
        }

        //проверяем самоуничтожения всех
        bool all_destroyed = true;
        for (auto& eater : eaters) {
            if (eater->alive) {
                all_destroyed = false;
                break;
            }
        }

        if (all_destroyed) {
            chef_no_salary = true;
            simulation_running = false;
            std::cout << "\nКук не получил зарплату, т.к. все толстяки самоуничтожились." << std::endl;
            return;
        }

        //готовка и выкладка наггетсов
        {
            MyLockGuard lock(mtx);  //наш lock_guard

            //проверка на пустые тарелки
            bool empty_dish = false;
            for (size_t i = 0; i < dishes.size(); i++) {
                if (eaters[i]->alive && dishes[i] <= 0) {
                    empty_dish = true;
                    break;
                }
            }

            if (empty_dish) {
                chef_fired = true;
                simulation_running = false;
                std::cout << "\nКук уволен! Закончились наггетсы." << std::endl;
                return;
            }

            //выкладка наггетсов
            for (size_t i = 0; i < dishes.size(); i++) {
                if (eaters[i]->alive) {
                    dishes[i] += efficiency;
                }
            }

            //разрешаем толстякам есть
            for (auto& eater : eaters) {
                if (eater->alive) {
                    eater->can_eat = true;
                }
            }
        }

        //небольшая пауза, чтобы толстяки могли поесть
        std::this_thread::sleep_for(std::chrono::milliseconds(10));
    }
}

void eat(Eater* eater) {
    while (simulation_running && !chef_fired && eater->alive) {
        //ждем разрешения есть
        if (eater->can_eat) {
            MyLockGuard lock(mtx);  // Используем наш lock_guard

            //проверка, что достаточно наггетсов
            if (eater->dish >= eater->gluttony) {
                eater->dish -= eater->gluttony;
                eater->eaten += eater->gluttony;

                //проверка, не съел ли слишком много
                if (eater->eaten >= DESTROY_THRESHOLD) {
                    eater->alive = false;
                    std::cout << eater->name << " самоуничтожился! Съел " << eater->eaten << " наггетсов." << std::endl;
                    eater->can_eat = false;
                    return;
                }

                //проверка, не опустела ли тарелка
                if (eater->dish <= 0) {
                    chef_fired = true;
                    simulation_running = false;
                    std::cout << eater->name << " опустошил тарелку! Кука уволили." << std::endl;
                    return;
                }
            }

            eater->can_eat = false;
        }

        std::this_thread::yield();
    }
}

int main() {
    setlocale(LC_ALL, "RU");
    std::cout << "--- Собственный мьютекс на основе атомарных переменных ---" << std::endl;
    std::cout << "--- Три сценария из lab4 ---" << std::endl;

    //1: Кука уволили (тарелки опустели)
    {
        std::cout << "\n--- Вариант 1: Кука уволили ---" << std::endl;
        std::vector<int> dishes = { INITIAL_DISH, INITIAL_DISH, INITIAL_DISH };
        int efficiency = 1;  //низкая производительность
        int gluttony1 = 100; //высокая прожорливость
        int gluttony2 = 100;
        int gluttony3 = 100;

        std::vector<Eater*> eaters = {
            new Eater(gluttony1, dishes[0], "Толстяк 1"),
            new Eater(gluttony2, dishes[1], "Толстяк 2"),
            new Eater(gluttony3, dishes[2], "Толстяк 3")
        };

        chef_fired = false;
        chef_left = false;
        chef_no_salary = false;
        chef_working = true;
        simulation_running = true;

        std::thread cook_thread(cook, efficiency, std::ref(eaters), std::ref(dishes));
        std::vector<std::thread> eater_threads;

        for (auto& eater : eaters) {
            eater_threads.emplace_back(eat, eater);
        }

        cook_thread.join();
        for (auto& thread : eater_threads) {
            thread.join();
        }

        std::cout << "Результат: efficiency=" << efficiency
            << ", gluttony1=" << gluttony1
            << ", gluttony2=" << gluttony2
            << ", gluttony3=" << gluttony3 << std::endl;

        //очистка
        for (auto& eater : eaters) {
            delete eater;
        }
    }

    //небольшая пауза между тестами
    std::this_thread::sleep_for(std::chrono::seconds(1));

    //2: Кук не получил зарплату (все толстяки самоуничтожились)
    {
        std::cout << "\n--- Вариант 2: Кук не получил зарплату ---" << std::endl;
        std::vector<int> dishes = { INITIAL_DISH, INITIAL_DISH, INITIAL_DISH };
        int efficiency = 100; //высокая производительность
        int gluttony1 = 50;   //средняя прожорливость
        int gluttony2 = 50;
        int gluttony3 = 50;

        std::vector<Eater*> eaters = {
            new Eater(gluttony1, dishes[0], "Толстяк 1"),
            new Eater(gluttony2, dishes[1], "Толстяк 2"),
            new Eater(gluttony3, dishes[2], "Толстяк 3")
        };

        chef_fired = false;
        chef_left = false;
        chef_no_salary = false;
        chef_working = true;
        simulation_running = true;

        std::thread cook_thread(cook, efficiency, std::ref(eaters), std::ref(dishes));
        std::vector<std::thread> eater_threads;

        for (auto& eater : eaters) {
            eater_threads.emplace_back(eat, eater);
        }

        cook_thread.join();
        for (auto& thread : eater_threads) {
            thread.join();
        }

        std::cout << "Результат: efficiency=" << efficiency
            << ", gluttony1=" << gluttony1
            << ", gluttony2=" << gluttony2
            << ", gluttony3=" << gluttony3 << std::endl;

        //очистка
        for (auto& eater : eaters) {
            delete eater;
        }
    }

    //небольшая пауза между тестами
    std::this_thread::sleep_for(std::chrono::seconds(1));

    //3: Кук уволился сам (прошло 5 дней)
    {
        std::cout << "\n--- Вариант 3: Кук уволился сам ---" << std::endl;
        std::vector<int> dishes = { INITIAL_DISH, INITIAL_DISH, INITIAL_DISH };
        int efficiency = 10;  //хорошая производительность
        int gluttony1 = 1;    //низкая прожорливость
        int gluttony2 = 1;
        int gluttony3 = 1;

        std::vector<Eater*> eaters = {
            new Eater(gluttony1, dishes[0], "Толстяк 1"),
            new Eater(gluttony2, dishes[1], "Толстяк 2"),
            new Eater(gluttony3, dishes[2], "Толстяк 3")
        };

        chef_fired = false;
        chef_left = false;
        chef_no_salary = false;
        chef_working = true;
        simulation_running = true;

        std::thread cook_thread(cook, efficiency, std::ref(eaters), std::ref(dishes));
        std::vector<std::thread> eater_threads;

        for (auto& eater : eaters) {
            eater_threads.emplace_back(eat, eater);
        }

        cook_thread.join();
        for (auto& thread : eater_threads) {
            thread.join();
        }

        std::cout << "Результат: efficiency=" << efficiency
            << ", gluttony1=" << gluttony1
            << ", gluttony2=" << gluttony2
            << ", gluttony3=" << gluttony3 << std::endl;

        //очистка
        for (auto& eater : eaters) {
            delete eater;
        }
    }

    return 0;
}