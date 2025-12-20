# Лабораторная работа №4
---
## Автор


Ус Владимир, 3МО-1

---
ТЗ: [Документ задания](files/OS_lab4.docx)
---

## Шаги
1. Исполните из командной строки ```Help Get-Command```:

<a href="screenshots/1.png"><img src="screenshots/1.png" alt="1_img" border="0"></a>  

2. Выполните команду ```Get-Command```:

<a href="screenshots/2.png"><img src="screenshots/2.png" alt="2_img" border="0"></a>      

3. Просмотрите список всех сервисов, запущенных на вашем компьютере, исполнив команду ```Get-Service```:

<a href="screenshots/3.png"><img src="screenshots/3.png" alt="3_img" border="0"></a>   

4. Просмотрите список всех процессов, запущенных в настоящий момент на вашем компьютере,исполнив команду ```Get-Process```:

<a href="screenshots/4.png"><img src="screenshots/4.png" alt="4_img" border="0"></a> 

5. Выполните команду ```Get-Process explorer```:

<a href="screenshots/5.png"><img src="screenshots/5.png" alt="5_img" border="0"></a>  

6. Из командной строки исполните команду ```Get-Process w*```:

<a href="screenshots/6.png"><img src="screenshots/6.png" alt="6_img" border="0"></a>  

7. Исполните команду ```Get-Process i* | format-list```:

<a href="screenshots/7.png"><img src="screenshots/7.png" alt="7_img" border="0"></a>

8. Для получения подробной информации о различных форматах можно использовать следующую команду ```Help format*```:

<a href="screenshots/8.png"><img src="screenshots/8.png" alt="8_img" border="0"></a>

9. Просмотрите все свойства объекта, полученного при выполнении команды Get-Process, используя следующую команду ```Get-Process | Get-Member```:

<a href="screenshots/9.png"><img src="screenshots/9.png" alt="9_img" border="0"></a>

10. Выполните операцию фильтрации, исполнив команду ```Get-Process | where {$_.handlecount -gt 400}```:

<a href="screenshots/10.png"><img src="screenshots/10.png" alt="10_img" border="0"></a>

11. Выполните операцию сортировки, исполнив команду ```Get-Process | where {$_.handlecount -gt 400} | sort-object Handles```:

<a href="screenshots/11.png"><img src="screenshots/11.png" alt="11_img" border="0"></a>

12. Произведем сортировку объектов по свойству WS (workingset) и выбор 5 процессов, занимающих больше всего памяти ```Get-Process | sort-object -property WS –descending| select-object -first 5```:

<a href="screenshots/12.png"><img src="screenshots/12.png" alt="12_img" border="0"></a>

13. Запустите Notepad. Выполнитекоманду ```Get-process notepad | stop-process```:

<a href="screenshots/13.png"><img src="screenshots/13.png" alt="13_img" border="0"></a>

14. ```Get-Process notepad | stop-process -whatif```:

<a href="screenshots/14.png"><img src="screenshots/14.png" alt="14_img" border="0"></a>

15. ```Get-Process notepad | stop-process -confirm```:

<a href="screenshots/15.png"><img src="screenshots/15.png" alt="15_img" border="0"></a>

16.	Создадим новый подкаталог TextFiles в текущем каталоге ```new-itemTextFiles -itemtype directory```:

<a href="screenshots/16.png"><img src="screenshots/16.png" alt="16_img" border="0"></a>

17. Создайте несколько новых файлов в текущем каталоге: ```psdemo.txt```, ```1.txt```, ```2.txt```:

<a href="screenshots/17.png"><img src="screenshots/17.png" alt="17_img" border="0"></a>

18.	Скопируем все файлы с расширением ```*.txt``` в подкаталог ```TextFiles```, используя команду ```copy-item```:

<a href="screenshots/18.png"><img src="screenshots/18.png" alt="18_img" border="0"></a>

19.	С помощью команды ```rename-item``` переименовываем файл ```psdemo.txt``` в ```psdemo.bak```. При необходимости можно применять опции ```-path``` и ```-newName``` ```rename-item psdemo.txt psdemo.bak```:

<a href="screenshots/19.png"><img src="screenshots/19.png" alt="19_img" border="0"></a>

20.	После того как файл переименован, переносим его на один уровень вверх, используя команду ```move-item``` ```move-itempsdemo.bak ..\```:

<a href="screenshots/20.png"><img src="screenshots/20.png" alt="20_img" border="0"></a>

21.	Манипуляции с файловой системой мы завершаем удалением всего каталога ```TextFiles```, используя команду ```remove-item```. Поскольку в каталоге ```TextFiles``` содержатся файлы, применяется опция ```-recurse``` ```remove-itemTextFiles–recurse```:

<a href="screenshots/21.png"><img src="screenshots/21.png" alt="21_img" border="0"></a>
