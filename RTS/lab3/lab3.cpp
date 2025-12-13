#include <iostream>
#include <thread>
#include <mutex>
#include <string>

int coins = 101;          
int initial_coins = coins;
int Bob_coins = 0;        
int Tom_coins = 0;       
std::mutex mtx;           //mutex для синхронизации (защищает здесь доступ)
bool bobs_turn = true;    

void coin_sharing(const std::string& thief_name) {
    while (true) {
        mtx.lock(); //лочим mutex (не доступен для остальных потоков)

        //конец дележки
        if (coins == 0) {
            mtx.unlock(); //анлочим mutex
            break;
        }
        if (initial_coins % 2 == 1 && coins == 1) {
            mtx.unlock(); //анлочим mutex
            break;
        }

        bool can_take = false;
        if (thief_name == "Bob" && bobs_turn) {
            if (Bob_coins <= Tom_coins) {
                can_take = true;
            }
        }
        else if (thief_name == "Tom" && !bobs_turn) {
            if (Tom_coins <= Bob_coins) {
                can_take = true;
            }
        }

        if (can_take) {
            coins--;
            if (thief_name == "Bob") {
                Bob_coins++;
                bobs_turn = false;
                std::cout << "Боб взял. Боб: " << Bob_coins << ", Том: " << Tom_coins << ", Осталось: " << coins << std::endl;
            }
            else {
                Tom_coins++;
                bobs_turn = true;
                std::cout << "Том взял. Боб: " << Bob_coins << ", Том: " << Tom_coins << ", Осталось: " << coins << std::endl;
            }
        }

        mtx.unlock(); //анлочим mutex
        std::this_thread::yield(); //пауза для работы других потоков
    }
}

int main() {
    setlocale(LC_ALL, "RU");
    std::cout << "Начинаем дележ " << coins << " монет..." << std::endl;

    std::thread bob_thread(coin_sharing, "Bob");
    std::thread tom_thread(coin_sharing, "Tom");

    bob_thread.join();
    tom_thread.join();

    std::cout << "\nДележ завершен!" << std::endl;
    std::cout << "Боб: " << Bob_coins << " монет" << std::endl;
    std::cout << "Том: " << Tom_coins << " монет" << std::endl;
    std::cout << "Не поделено: " << coins << " монет" << std::endl;
    std::cout << "Всего: " << Bob_coins + Tom_coins + coins << std::endl;

    if (Bob_coins + Tom_coins + coins == initial_coins) {
        std::cout << "Сумма правильная: " << initial_coins << std::endl;
    }

    int diff = std::abs(Bob_coins - Tom_coins);
    if (diff <= 1) {
        std::cout << "Разница: " << diff << std::endl;
    }

    if (initial_coins % 2 == 1 && coins == 1) {
        std::cout << "Монета покойнику!" << std::endl;
    }
    else if (initial_coins % 2 == 0 && coins == 0) {
        std::cout << "Все монеты поделены" << std::endl;
    }

    return 0;
}
