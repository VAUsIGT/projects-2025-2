#include <coroutine>
#include <iostream>
#include <chrono>
#include <thread>
using namespace std;

/*
struct CoroutineResult {
    struct promise_type;
    using handle_type = std::coroutine_handle<promise_type>;

    struct promise_type {
        int current_value;

        CoroutineResult get_return_object() {
            return CoroutineResult{ handle_type::from_promise(*this) };
        }

        std::suspend_always initial_suspend() { return {}; }
        std::suspend_always final_suspend() noexcept { return {}; }
        void return_void() {}
        void unhandled_exception() {}

        std::suspend_always yield_value(int value) {
            current_value = value;
            return {};
        }
    };

    handle_type coro_handle;

    //конструктор
    explicit CoroutineResult(handle_type h) : coro_handle(h) {}

    //деструктор
    ~CoroutineResult() {
        if (coro_handle) {
            coro_handle.destroy();
        }
    }

    int value() const {
        return coro_handle.promise().current_value;
    }

    bool resume() {
        if (!coro_handle.done()) {
            coro_handle.resume();
        }
        return !coro_handle.done();
    }

    bool done() const {
        return coro_handle.done();
    }
};

CoroutineResult generate_numbers() {
    int my_number = 167;
    std::cout << "Генерация чисел из числа " << my_number << std::endl;
    co_yield my_number;           //1 число
    co_yield my_number * 2;       //2
    co_yield my_number + 10;      //3
    co_yield my_number - 5;       //4
    co_yield my_number * my_number; //5
}

int main() {
    setlocale(LC_ALL, "Russian");

    CoroutineResult numbers = generate_numbers();
    int counter = 1;
    while (numbers.resume()) {
        std::cout << "Число " << counter << ": " << numbers.value() << std::endl;
        counter++;
    }
    std::cout << std::endl;
    std::cout << "Корутина завершила выполнение." << std::endl;

    return 0;
}
*/

struct promise;

struct promise_type
{
    int current_value = 0;

    auto get_return_object()
    {
        return std::coroutine_handle<promise_type>::from_promise(*this);
    }

    std::suspend_always initial_suspend() { return {}; }
    std::suspend_always final_suspend() noexcept { return {}; }
    void return_void() {}
    void unhandled_exception() {}

    std::suspend_always yield_value(int value)
    {
        current_value = value;
        return {};
    }
};

struct coroutine : std::coroutine_handle<promise> {
    using promise_type = ::promise;
};


struct promise
{
    coroutine get_return_object() { return { coroutine::from_promise(*this) }; }
    std::suspend_always initial_suspend() noexcept { return {}; }
    std::suspend_always final_suspend() noexcept { return {}; }
    void return_void() {}
    void unhandled_exception() {}
};
struct task
{
    std::coroutine_handle<promise_type> handle;

    task(std::coroutine_handle<promise_type> h) : handle(h) {}
    ~task() { if (handle) handle.destroy(); }

    void resume() { handle.resume(); }
    bool done() const { return handle.done(); }
    int get_value() const { return handle.promise().current_value; }
};
namespace std
{
    template<>
    struct coroutine_traits<task, int>
    {
        using promise_type = promise_type;
    };
}

task long_computation(int steps) {
    for (int i = 1; i <= steps; ++i) {
        std::this_thread::sleep_for(std::chrono::milliseconds(50));
        co_yield i;
    }
    co_return;
}
void print_progress(int current, int total) {
    float percent = static_cast<float>(current) / total * 100.f;
    int bar_width = 50;
    std::string name = "Vladimir";
    std::string bar(bar_width, ' ');
    size_t pos = static_cast<size_t>(percent / 100.f * bar_width);
    for (size_t i = 0; i < pos; ++i) {
        bar[i] = name[i % name.length()];
    }
    if (pos > 0 && pos <= bar.size()) bar[pos - 1] = '=>';

    std::cout << "\r[" << bar << "]" << std::fixed << std::setprecision(1) << percent << "%";
    std::flush(std::cout);
}


int main()
{
    constexpr int TOTAL_STEPS = 100;
    auto coro = long_computation(TOTAL_STEPS);

    while (!coro.done()) {
        coro.resume();
        int progress = coro.get_value();
        print_progress(progress, TOTAL_STEPS);
        std::this_thread::yield();
    }

    std::cout << "\ndone" << std::endl;

    return 0;

}
