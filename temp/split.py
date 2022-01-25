import time
start = time.time()

for v in range(1,80):
  end = time.time()
  hours, rem = divmod(end-start, 3600)
  minutes, seconds = divmod(rem, 60)
  print("Time elapsed: {:0>2}:{:0>2}:{:05.2f}".format(int(hours),int(minutes),seconds))
  time.sleep(3)