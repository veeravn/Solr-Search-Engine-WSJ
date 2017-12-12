
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

import java.io.*;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;

public class ExternalPageRank {

	public static void main(String[] args) throws Exception {

		File dir = new File("../../../Desktop/WSJ/WSJ");

		File text = new File("output/edgeList.txt");
		text.getParentFile().mkdirs();

		PrintWriter writer = new PrintWriter(text);
		HashMap<String, String> fileUrlMap = new HashMap<String, String>();
		HashMap<String, String> urlFileMap = new HashMap<String, String>();
		Set<String> edges = new HashSet<String>();

		// parse csv files
		String csvFile = "../../../Desktop/WSJ/WSJmap.csv";
		BufferedReader br = null;
		String line = "";
		String cvsSplitBy = ",";

		br = new BufferedReader(new FileReader(csvFile));
		while ((line = br.readLine()) != null) {

			// use comma as separator
			String[] data = line.split(cvsSplitBy);

			fileUrlMap.put(data[0], data[1]);
			urlFileMap.put(data[1], data[0]);
		}
		br.close();
		//make sure that only the files with a name length of ten or more characters are used.
		FilenameFilter filter = new FilenameFilter() {
			
			@Override
			public boolean accept(File dir, String name) {
				return !(name.length() < 10);
			}
		};
		File[] files = dir.listFiles(filter);
		for (File file : files) {

			Document doc = Jsoup.parse(file, "UTF-8", fileUrlMap.get(file.getName()));
			Elements links = doc.select("a[href]");
			Elements pages = doc.select("[src]");

			for (Element link : links) {
				String url = link.attr("abs:href".trim());
				if (urlFileMap.containsKey(url)) {
					String prefix = "WSJ/WSJ/";
					edges.add(prefix + file.getName() + " " + prefix + urlFileMap.get(url));
				}
			}
		}

		for (String s : edges) {
			writer.println(s);

		}

		System.out.println(edges.size());
		writer.flush();
		writer.close();

	}
}
